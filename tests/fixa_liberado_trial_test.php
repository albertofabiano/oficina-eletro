<?php
/*
 * Testes de financeiro_pessoal_liberado() (app/Helpers/functions.php) — pedido do usuário: o
 * teste grátis de 7 dias também deve liberar a Carteira Fixa (era só plano pago
 * autonomo/oficina/empresa antes). Mesmo runner mínimo do projeto (sem PHPUnit/Composer, ver
 * CLAUDE.md). Rodar com: php tests/fixa_liberado_trial_test.php
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

$amanha = date('Y-m-d', strtotime('+1 day'));
$ontem  = date('Y-m-d', strtotime('-1 day'));

echo "-- plano pago de verdade continua liberando (comportamento antigo, sem regressão) --\n";
{
    assert_igual(true, financeiro_pessoal_liberado(['reivindicada' => 1, 'plano_atual' => 'autonomo']), 'reivindicada + Autônomo');
    assert_igual(true, financeiro_pessoal_liberado(['reivindicada' => 1, 'plano_atual' => 'oficina']), 'reivindicada + Oficina');
    assert_igual(true, financeiro_pessoal_liberado(['reivindicada' => 1, 'plano_atual' => 'empresa']), 'reivindicada + Empresa (Top)');
    assert_igual(false, financeiro_pessoal_liberado(['reivindicada' => 0, 'plano_atual' => 'autonomo']), 'plano pago mas NÃO reivindicada não libera');
    assert_igual(false, financeiro_pessoal_liberado(['reivindicada' => 1, 'plano_atual' => 'basico']), 'reivindicada mas plano Básico não libera sozinho');
}

echo "\n-- trial ativo (trial_ate >= hoje) também libera, mesmo sem plano_atual pago --\n";
{
    assert_igual(true, financeiro_pessoal_liberado(['reivindicada' => 1, 'plano_atual' => null, 'trial_ate' => $amanha]), 'trial_ate amanhã, sem plano_atual nenhum');
    assert_igual(true, financeiro_pessoal_liberado(['reivindicada' => 1, 'plano_atual' => '', 'trial_ate' => date('Y-m-d')]), 'trial_ate HOJE ainda conta (vence hoje inclusive)');
}

echo "\n-- trial vencido, sem reivindicação, ou sem trial algum: não libera por esse caminho --\n";
{
    assert_igual(false, financeiro_pessoal_liberado(['reivindicada' => 1, 'plano_atual' => null, 'trial_ate' => $ontem]), 'trial_ate ontem (já venceu) não libera');
    assert_igual(false, financeiro_pessoal_liberado(['reivindicada' => 0, 'plano_atual' => null, 'trial_ate' => $amanha]), 'trial ativo mas NÃO reivindicada (ex.: conta só-diretório) não libera');
    assert_igual(false, financeiro_pessoal_liberado(['reivindicada' => 1, 'plano_atual' => null, 'trial_ate' => null]), 'sem trial_ate nenhum e sem plano pago');
    assert_igual(false, financeiro_pessoal_liberado(['reivindicada' => 1]), 'empresa sem nenhuma das chaves (array mínimo)');
}

echo "\n-- assinatura Fixa standalone continua sendo o 3º caminho, independente dos outros dois --\n";
{
    assert_igual(true, financeiro_pessoal_liberado(['reivindicada' => 0, 'plano_atual' => null, '_fixa_standalone_liberado' => true]), 'standalone libera mesmo sem reivindicação nem trial');
    assert_igual(false, financeiro_pessoal_liberado(['reivindicada' => 1, 'plano_atual' => 'basico', 'trial_ate' => $ontem, '_fixa_standalone_liberado' => false]), 'nenhum dos 3 caminhos libera (trial vencido, plano básico, standalone recusado)');
}

echo "\n" . str_repeat('-', 60) . "\n";
echo $total . " verificações, " . $falhas . " falha(s).\n";
exit($falhas > 0 ? 1 : 0);
