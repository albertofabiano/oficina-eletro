<?php
// Coleta agendada do módulo Marketing (tráfego pago) — roda SyncService::syncAllAccounts()
// pra toda conta ativa de empresa com `marketing_habilitado=1`, qualquer plataforma
// (App\Services\Marketing\PlatformFactory decide qual classe usar por conta). Uma falha numa
// conta nunca trava as outras (SyncService já garante isso, ver markFailed()/audit()).
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
