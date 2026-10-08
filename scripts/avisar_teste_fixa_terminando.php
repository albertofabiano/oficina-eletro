<?php
// Fixa standalone — aviso do dia 5 do teste de 7 dias (Etapa 3 do pedido de cobrança: "faltam
// 2 dias, no dia X você será cobrado R$Y", com link de cancelamento em 1 clique). E-mail
// (EmailService::avisoTesteFixaTerminando()) + notificação in-app (NotificacaoService::criar(),
// aparece no mesmo sino da topbar — todo usuário tem empresa_id, mesmo o Fixa standalone, que
// cria uma empresa "casca" por baixo — ver CLAUDE.md "Cadastro próprio e simples").
//
// Dedup: fixa_assinatura_avisos (UNIQUE assinatura_id+tipo) — nunca reenvia pra mesma assinatura,
// então é seguro rodar todo dia (o filtro de SQL já restringe à janela de 48h antes do teste_fim,
// mas quem garante "só 1 vez" de verdade é essa tabela, não a janela de tempo).
//
// Rodar via cron real, 1x/dia, ex.:
//   0 9 * * * php /var/www/fixaos/scripts/avisar_teste_fixa_terminando.php >> /var/www/fixaos/storage/logs/fixa_aviso_teste_cron.log 2>&1

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});
require BASE_PATH . '/app/Helpers/functions.php';

use App\Core\DB;
use App\Services\EmailService;
use App\Services\NotificacaoService;
use App\Services\Fixa\AssinaturaService;

$appConfig = require BASE_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);
$baseUrl = rtrim($appConfig['url'], '/');

$db = DB::pdo();

$stmt = $db->prepare(
    "SELECT a.*, u.nome AS usuario_nome, u.email AS usuario_email, u.empresa_id AS usuario_empresa_id
     FROM fixa_assinaturas a
     JOIN usuarios u ON u.id = a.usuario_id
     WHERE a.status = 'teste'
       AND a.teste_fim BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)
       AND a.cancelar_token IS NOT NULL
       AND NOT EXISTS (
         SELECT 1 FROM fixa_assinatura_avisos av
         WHERE av.assinatura_id = a.id AND av.tipo = 'teste_terminando'
       )"
);
$stmt->execute();
$candidatas = $stmt->fetchAll();

$cfgFixa = AssinaturaService::config();
$nomePlano = function (string $codigo) use ($cfgFixa): string {
    foreach ($cfgFixa['planos'] as $p) if ($p['codigo'] === $codigo) return $p['nome'];
    return $codigo;
};

$enviados = 0;
foreach ($candidatas as $a) {
    // Re-confere com a MESMA regra de AssinaturaService (não confia só no filtro de SQL —
    // mesmo princípio de nunca duplicar a lógica de decisão em dois lugares que podem divergir).
    if (!AssinaturaService::precisaAvisoTesteAcabando($a)) continue;
    if (empty($a['usuario_email'])) continue;

    $dataCobranca = date('d/m/Y', strtotime($a['teste_fim'] . ' +1 day'));
    $valorFormatado = money(((int) $a['valor_centavos']) / 100);
    $cancelarUrl = $baseUrl . '/fixa/cancelar-teste/' . $a['cancelar_token'];

    $ok = EmailService::avisoTesteFixaTerminando(
        $a['usuario_email'], (string) $a['usuario_nome'], $nomePlano($a['plano']), $dataCobranca, $valorFormatado, $cancelarUrl
    );

    if ($ok) {
        NotificacaoService::criar(
            (int) $a['usuario_empresa_id'],
            'fixa_teste_terminando',
            'Seu teste do Carteira Fixa termina em 2 dias',
            "No dia {$dataCobranca} você será cobrado {$valorFormatado}. Cancele em 1 clique no e-mail que acabamos de enviar, se preferir.",
            '/financeiro-pessoal',
            'bi-clock-history',
            'warning',
            (int) $a['usuario_id']
        );
        $db->prepare("INSERT INTO fixa_assinatura_avisos (assinatura_id, tipo) VALUES (?, 'teste_terminando')")
            ->execute([$a['id']]);
        $enviados++;
    }
}

printf("[%s] candidatas=%d enviados=%d\n", date('Y-m-d H:i:s'), count($candidatas), $enviados);
