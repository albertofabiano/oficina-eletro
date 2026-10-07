<?php
/*
 * Testes de fixa_saldo_atual()/fixa_saldo_previsto() (app/Helpers/functions.php).
 * Rodar com: php tests/fixa_saldo_test.php
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

echo "-- fixa_saldo_atual: saldo_inicial + receitas pagas - despesas pagas --\n";
{
    assert_igual(1500.0, fixa_saldo_atual(1000.0, 1000.0, 500.0), 'caso simples positivo');
    assert_igual(0.0, fixa_saldo_atual(0.0, 0.0, 0.0), 'tudo zerado');
    assert_igual(-200.0, fixa_saldo_atual(0.0, 300.0, 500.0), 'despesa paga maior que receita paga, saldo negativo');
    assert_igual(100.0, fixa_saldo_atual(100.0, 0.0, 0.0), 'só saldo inicial, sem nenhum lançamento pago ainda');
    // Arredondamento — soma com erro de ponto flutuante clássico (0.1 + 0.2) não deve vazar
    // pro resultado final.
    assert_igual(0.3, fixa_saldo_atual(0.1, 0.2, 0.0), 'arredonda pra 2 casas, sem resíduo de float');
}

echo "\n-- fixa_saldo_previsto: saldo atual + a receber até fim do mês - a pagar até fim do mês --\n";
{
    assert_igual(1700.0, fixa_saldo_previsto(1500.0, 500.0, 300.0), 'caso simples: mais a receber que a pagar');
    assert_igual(1000.0, fixa_saldo_previsto(1500.0, 0.0, 500.0), 'só tem a pagar, nada a receber');
    assert_igual(2000.0, fixa_saldo_previsto(1500.0, 500.0, 0.0), 'só tem a receber, nada a pagar');
    assert_igual(-100.0, fixa_saldo_previsto(500.0, 100.0, 700.0), 'previsto fica negativo quando a pagar supera o que há + a receber');
    assert_igual(1500.0, fixa_saldo_previsto(1500.0, 0.0, 0.0), 'sem nada em aberto, previsto = saldo atual');
}

echo "\n-- Ponta a ponta: cenário realista de um mês --\n";
{
    // Conta "Carteira": saldo inicial R$200. No mês, pagou R$800 de despesa, recebeu R$1200 de
    // salário (já pago). Ainda tem R$300 de despesa em aberto (conta de luz não paga) e R$150
    // de receita a receber (reembolso prometido) até o fim do mês.
    $saldoAtual = fixa_saldo_atual(200.0, 1200.0, 800.0);
    assert_igual(600.0, $saldoAtual, 'saldo atual depois do mês de movimento pago');
    $saldoPrevisto = fixa_saldo_previsto($saldoAtual, 150.0, 300.0);
    assert_igual(450.0, $saldoPrevisto, 'saldo previsto até o fim do mês, considerando o que ainda está em aberto');
}

echo "\n" . str_repeat('-', 60) . "\n";
echo $total . " verificações, " . $falhas . " falha(s).\n";
exit($falhas > 0 ? 1 : 0);
