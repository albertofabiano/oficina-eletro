<?php
/*
 * Testa App\Services\Fixa\FormaPagamentoService::resolverParcelas() contra os 9 casos reais já
 * validados em `datas.mjs` (o roteiro do usuário, ROTEIRO-carteira-fixa.md §4) — porta fiel
 * dessa mesma lógica (addMeses/addDias/vencimentoFatura/ratear) de JS pra PHP, mesma aritmética.
 *
 * O caso mais importante é o último (carnê 3x a partir de 31/jan): fevereiro encurta pra 28,
 * mas março VOLTA pra 31 — só acontece porque addMeses() ancora sempre na data ORIGINAL (31),
 * nunca na data já calculada/encurtada da parcela anterior.
 *
 * Rodar com: php tests/fixa_formas_pagamento_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Services/Fixa/FormaPagamentoService.php';

use App\Services\Fixa\FormaPagamentoService as FP;

$falhas = 0; $total = 0;
function assert_igual($esperado, $obtido, string $descricao): void
{
    global $falhas, $total;
    $total++;
    if ($esperado === $obtido) { echo "  OK  $descricao\n"; return; }
    $falhas++;
    echo "FALHA $descricao\n      esperado: " . var_export($esperado, true) . "\n      obtido:   " . var_export($obtido, true) . "\n";
}

$nubank = ['tipo' => 'cartao', 'dia_fechamento' => 28, 'dia_vencimento' => 5];
$itau   = ['tipo' => 'cartao', 'dia_fechamento' => 5,  'dia_vencimento' => 15];
$pix    = ['tipo' => 'simples'];
$stone  = ['tipo' => 'recebivel', 'prazo_dias' => 30];

function datas(array $res): array { return array_column($res, 'data'); }

assert_igual(['2026-11-05'], datas(FP::resolverParcelas($nubank, '2026-10-10', 200, 1)), 'cartão 28/5 · compra 10/out');
assert_igual(['2026-11-05'], datas(FP::resolverParcelas($nubank, '2026-10-28', 200, 1)), 'cartão 28/5 · compra 28/out');
assert_igual(['2026-12-05'], datas(FP::resolverParcelas($nubank, '2026-10-29', 200, 1)), 'cartão 28/5 · compra 29/out (passou do fechamento)');
assert_igual(['2026-10-15'], datas(FP::resolverParcelas($itau, '2026-10-03', 200, 1)), 'cartão 5/15 · compra 03/out');
assert_igual(['2026-11-15'], datas(FP::resolverParcelas($itau, '2026-10-06', 200, 1)), 'cartão 5/15 · compra 06/out (passou do fechamento)');

$r = FP::resolverParcelas($nubank, '2026-10-10', 1000, 3);
assert_igual(['2026-11-05', '2026-12-05', '2027-01-05'], datas($r), 'cartão 28/5 · 1000 em 3x 10/out');
assert_igual([333.34, 333.33, 333.33], array_column($r, 'valor'), 'rateio de 1000 em 3x (sobra na 1ª parcela)');

$r = FP::resolverParcelas($stone, '2026-10-10', 900, 3);
assert_igual(['2026-11-09', '2026-12-09', '2027-01-08'], datas($r), 'maquininha D+30 · 3x 10/out');

assert_igual(['2026-10-14'], datas(FP::resolverParcelas($pix, '2026-10-14', 189.40, 1)), 'pix · boleto vence 14/out');

$r = FP::resolverParcelas($pix, '2027-01-31', 300, 3);
assert_igual(['2027-01-31', '2027-02-28', '2027-03-31'], datas($r), 'carnê simples 3x a partir de 31/jan (fev encurta, mar volta pra 31)');

// Rateio isolado, confirmando soma exata (sem perda/sobra de centavo por arredondamento de float).
$rateio1 = FP::ratear(1000, 3);
assert_igual(round(array_sum($rateio1), 2), 1000.0, 'rateio 1000/3 soma exatamente 1000');
$rateio2 = FP::ratear(189.40, 7);
assert_igual(round(array_sum($rateio2), 2), 189.40, 'rateio 189,40/7 soma exatamente 189,40');

echo "\n" . str_repeat('-', 60) . "\n";
echo $total . " verificações, " . $falhas . " falha(s).\n";
exit($falhas > 0 ? 1 : 0);
