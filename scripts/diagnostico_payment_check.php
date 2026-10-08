<?php
// Diagnóstico — SÓ LEITURA, não grava/altera nada em `cobrancas` nem credita nada em lugar
// nenhum. Objetivo único: capturar a resposta REAL de InfinitePayService::verificarPagamento()
// (o endpoint /payment_check) pra uma cobrança PENDENTE e pra uma cobrança PAGA, e mostrar os
// campos exatos que a InfinitePay devolve — hoje a checagem em PagamentoController::webhook()
// aceita `success` sozinho OU uma lista de strings de status adivinhadas ('approved','captured'
// etc.), o que é sinal de que ninguém confirmou isso contra uma resposta de verdade. Antes de
// apertar essa checagem (exigir success===true && paid===true && valor batendo), precisamos
// saber os nomes/tipos reais dos campos.
//
// Só funciona com as credenciais reais da InfinitePay (config/infinitepay.php, gitignored) —
// por isso só roda no VPS, nunca neste sandbox de desenvolvimento.
//
// Rodar manualmente (não é cron, é diagnóstico pontual):
//   php scripts/diagnostico_payment_check.php
//
// Saída: imprime no console E grava em storage/logs/diagnostico_payment_check.log (acrescenta,
// não sobrescreve — cada rodada fica registrada).

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});
require BASE_PATH . '/app/Helpers/functions.php';

use App\Core\DB;
use App\Services\InfinitePayService;

$appConfig = require BASE_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

$logPath = BASE_PATH . '/storage/logs/diagnostico_payment_check.log';
$linhas = [];
$registrar = function (string $texto) use (&$linhas) {
    $linhas[] = $texto;
    echo $texto . "\n";
};

$registrar('=== Diagnóstico payment_check — ' . date('Y-m-d H:i:s') . ' ===');

if (!InfinitePayService::ativo()) {
    $registrar('InfinitePayService::ativo() === false — confirme config/infinitepay.php (handle/ativo) antes de rodar.');
    file_put_contents($logPath, implode("\n", $linhas) . "\n\n", FILE_APPEND);
    exit(1);
}

$db = DB::pdo();

$pendente = $db->query(
    "SELECT * FROM cobrancas WHERE status = 'pendente' ORDER BY id DESC LIMIT 1"
)->fetch();

$pago = $db->query(
    "SELECT * FROM cobrancas WHERE status = 'pago' ORDER BY id DESC LIMIT 1"
)->fetch();

$inspecionar = function (?array $c, string $rotulo) use ($registrar) {
    $registrar('');
    $registrar("--- {$rotulo} ---");
    if (!$c) {
        $registrar('Nenhuma cobrança encontrada nesse status.');
        return;
    }
    $registrar(sprintf(
        'cobrancas.id=%d order_nsu=%s valor(centavos)=%d transaction_nsu=%s invoice_slug=%s',
        (int) $c['id'], $c['order_nsu'], (int) $c['valor'],
        $c['transaction_nsu'] ?: '(vazio)', $c['invoice_slug'] ?: '(vazio)'
    ));

    $resp = InfinitePayService::verificarPagamento(
        (string) $c['order_nsu'],
        (string) ($c['transaction_nsu'] ?? ''),
        (string) ($c['invoice_slug'] ?? '')
    );

    $registrar('Resposta de verificarPagamento() — campos e tipos:');
    foreach ($resp as $campo => $valor) {
        $registrar(sprintf('  %-20s (%s) = %s', $campo, gettype($valor), var_export($valor, true)));
    }
    if (!$resp) {
        $registrar('  (array vazio — ver error_log do PHP pra detalhe da falha HTTP, InfinitePayService::post() já loga isso)');
    }
    $registrar('Resposta crua completa (var_export):');
    $registrar('  ' . str_replace("\n", "\n  ", var_export($resp, true)));
};

$inspecionar($pendente, 'Cobrança PENDENTE mais recente');
$inspecionar($pago, 'Cobrança PAGA mais recente');

$registrar('');
$registrar('=== Fim do diagnóstico — nada foi alterado no banco ===');

file_put_contents($logPath, implode("\n", $linhas) . "\n\n", FILE_APPEND);
