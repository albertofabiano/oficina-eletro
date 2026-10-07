<?php
/*
 * Testes de fixa_status_lancamento()/fixa_status_rotulo() (app/Helpers/functions.php) — o
 * status de um lançamento do Fixa é SEMPRE calculado (pago/vencido/a_pagar/a_receber), nunca
 * gravado numa coluna própria (ver pedido original da Fase 1: "Status é calculado, não
 * gravado"). Mesmo runner mínimo do projeto (sem PHPUnit/Composer, ver CLAUDE.md).
 * Rodar com: php tests/fixa_status_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Helpers/functions.php';

$falhas = 0; $total = 0;
function assert_igual($esperado, $obtido, string $descricao): void
{
    global $falhas, $total;
    $total++;
    if ($esperado === $obtido) { echo "  OK  $descricao\n"; return; }
    $falhas++;
    echo "FALHA $descricao\n      esperado: " . var_export($esperado, true) . "\n      obtido:   " . var_export($obtido, true) . "\n";
}

$hoje = '2026-10-15';

echo "-- pago: pago_em preenchido sempre vence, não importa o vencimento --\n";
{
    assert_igual('pago', fixa_status_lancamento('2026-10-10', '2026-10-05', 'despesa', $hoje), 'pago com vencimento já passado também é "pago"');
    assert_igual('pago', fixa_status_lancamento('2026-10-10', null, 'despesa', $hoje), 'pago sem vencimento nenhum');
    assert_igual('pago', fixa_status_lancamento('2026-10-10', '2026-12-01', 'receita', $hoje), 'pago com vencimento no futuro ainda é "pago"');
}

echo "\n-- vencido: sem pago_em, vencimento no passado --\n";
{
    assert_igual('vencido', fixa_status_lancamento(null, '2026-10-01', 'despesa', $hoje), 'despesa vencida');
    assert_igual('vencido', fixa_status_lancamento(null, '2026-10-14', 'receita', $hoje), 'vencimento ONTEM conta como vencido');
    assert_igual('vencido', fixa_status_lancamento('', '2026-10-01', 'despesa', $hoje), 'pago_em string vazia conta como "sem pagamento"');
}

echo "\n-- a_pagar / a_receber: sem pago_em, sem vencimento vencido --\n";
{
    assert_igual('a_pagar', fixa_status_lancamento(null, '2026-10-15', 'despesa', $hoje), 'vencimento HOJE ainda não é vencido (só < hoje conta)');
    assert_igual('a_pagar', fixa_status_lancamento(null, '2026-11-01', 'despesa', $hoje), 'despesa com vencimento futuro');
    assert_igual('a_pagar', fixa_status_lancamento(null, null, 'despesa', $hoje), 'despesa sem vencimento nenhum, nunca paga');
    assert_igual('a_receber', fixa_status_lancamento(null, '2026-11-01', 'receita', $hoje), 'receita com vencimento futuro');
    assert_igual('a_receber', fixa_status_lancamento(null, null, 'receita', $hoje), 'receita sem vencimento nenhum, nunca paga');
}

echo "\n-- hoje padrão (sem 4º argumento) usa date('Y-m-d') de verdade --\n";
{
    $ontem = date('Y-m-d', strtotime('-1 day'));
    $amanha = date('Y-m-d', strtotime('+1 day'));
    assert_igual('vencido', fixa_status_lancamento(null, $ontem, 'despesa'), 'sem 4º argumento, compara contra hoje de verdade (vencido)');
    assert_igual('a_pagar', fixa_status_lancamento(null, $amanha, 'despesa'), 'sem 4º argumento, compara contra hoje de verdade (ainda não vencido)');
}

echo "\n-- rótulos em português --\n";
{
    assert_igual('Pago', fixa_status_rotulo('pago'), 'rótulo pago');
    assert_igual('Vencido', fixa_status_rotulo('vencido'), 'rótulo vencido');
    assert_igual('A pagar', fixa_status_rotulo('a_pagar'), 'rótulo a_pagar');
    assert_igual('A receber', fixa_status_rotulo('a_receber'), 'rótulo a_receber');
}

echo "\n" . str_repeat('-', 60) . "\n";
echo $total . " verificações, " . $falhas . " falha(s).\n";
exit($falhas > 0 ? 1 : 0);
