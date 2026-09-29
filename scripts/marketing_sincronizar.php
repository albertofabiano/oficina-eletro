<?php
// Coleta agendada do módulo Marketing (tráfego pago) — roda SyncService::syncAllAccounts()
// pra toda conta ativa de empresa com `marketing_habilitado=1`, qualquer plataforma
// (App\Services\Marketing\PlatformFactory decide qual classe usar por conta). Uma falha numa
// conta nunca trava as outras (SyncService já garante isso, ver markFailed()/audit()).
//
// Depois da coleta, roda a otimização (Etapa 3, ver especificacao-modulo-marketing-fixaos.md
// seção 6): gera sugestões novas (QueueService::gerarSugestoes()) e executa qualquer pedido já
// aprovado (QueueService::executarAprovados()) — pego aqui, e não só no botão "Aprovar" da
// tela, cobre o caso de alguém aprovar e o clique de executar falhar por algo transitório.
//
// Este é o caminho RECOMENDADO em produção — sem cron real, o painel só sincroniza sozinho na
// primeira visita de cada empresa (MarketingController::painel()) ou quando alguém clica em
// "Sincronizar agora" (cooldown de 60s) — não existe poller throttled tipo o de notificações/
// lembretes, porque não faz sentido a coleta de anúncio depender de alguém ter o FixaOS aberto.
//
// Recomendado rodar a cada 6 horas (métrica de anúncio não muda tão rápido a ponto de precisar
// de mais frequência que isso, e reduz o número de chamadas às APIs da Meta/Google):
//   0 */6 * * * php /var/www/fixaos/scripts/marketing_sincronizar.php >> /var/www/fixaos/storage/logs/marketing_cron.log 2>&1
//
// Uso: php scripts/marketing_sincronizar.php [--empresa=ID]

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});
require BASE_PATH . '/app/Helpers/functions.php';

$appConfig = require BASE_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

$empresaId = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--empresa=')) $empresaId = (int) substr($arg, 10);
}

$db = App\Core\DB::pdo();
$sync = new App\Services\Marketing\SyncService($db);

$resultados = $sync->syncAllAccounts(
    fn(array $conta) => App\Services\Marketing\PlatformFactory::make($db, $conta),
    $empresaId
);

$ok = count(array_filter($resultados, fn($r) => $r['ok']));
$falhas = count($resultados) - $ok;

printf("[%s] marketing_sincronizar: %d conta(s) processada(s), %d ok, %d falha(s)\n", date('Y-m-d H:i:s'), count($resultados), $ok, $falhas);
foreach ($resultados as $r) {
    if (!$r['ok']) printf("  falhou conta #%d: %s\n", $r['account_id'], $r['error']);
}

$dryRun = App\Services\Marketing\MarketingConfig::isDryRun();

$stmtEmpresas = $db->prepare('SELECT id FROM empresas WHERE marketing_habilitado = 1' . ($empresaId !== null ? ' AND id = ?' : ''));
$stmtEmpresas->execute($empresaId !== null ? [$empresaId] : []);
$empresas = $stmtEmpresas->fetchAll(PDO::FETCH_COLUMN);

$queue = new App\Services\Marketing\QueueService($db);
$totalSugestoes = 0; $totalExecutados = 0; $totalFalhasExecucao = 0;
foreach ($empresas as $eid) {
    $eid = (int) $eid;
    try {
        $totalSugestoes += $queue->gerarSugestoes($eid);
        $execucoes = $queue->executarAprovados(fn(array $alvo) => App\Services\Marketing\PlatformFactory::make($db, $alvo), $dryRun, $eid);
        foreach ($execucoes as $ex) {
            if ($ex['status'] === 'executed') $totalExecutados++;
            elseif ($ex['status'] === 'failed') $totalFalhasExecucao++;
        }
    } catch (\Throwable $e) {
        printf("  otimização falhou pra empresa #%d: %s\n", $eid, $e->getMessage());
    }
}
printf(
    "[%s] marketing_sincronizar (otimização): %d sugestão(ões) nova(s), %d executada(s), %d falha(s) de execução\n",
    date('Y-m-d H:i:s'), $totalSugestoes, $totalExecutados, $totalFalhasExecucao
);
