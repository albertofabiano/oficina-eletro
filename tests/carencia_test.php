<?php
/*
 * Carência de pagamento do plano completo (sistema_bloqueado()/empresa_em_carencia(),
 * app/Helpers/functions.php) — pedido explícito: depois de trial_ate/licenca_ate vencer, o
 * sistema continua liberado por `config/app.php['carencia_dias']` dias (hoje 3) antes de
 * bloquear de verdade. Funções puras sobre array, testadas DE VERDADE (sem reimplementar),
 * contra a config real do repositório (carencia_dias=3, cobranca_ativa=true).
 *
 * Rodar com: php tests/carencia_test.php
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

$cfgApp = require BASE_PATH . '/config/app.php';
$carenciaDias = (int) ($cfgApp['carencia_dias'] ?? 0);
assert_igual(3, $carenciaDias, 'pré-condição: config/app.php tem carencia_dias=3 (se isso mudou, os casos abaixo precisam ser recalculados)');

function dataRelativa(int $diasOffset): string
{
    return date('Y-m-d', strtotime("{$diasOffset} days"));
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== sistema_bloqueado() ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    assert_igual(true, sistema_bloqueado([]), 'nunca teve trial_ate nem licenca_ate: bloqueado direto, sem carência');
    assert_igual(true, sistema_bloqueado(['trial_ate' => null, 'licenca_ate' => null]), 'os dois explicitamente null: mesmo resultado');

    assert_igual(false, sistema_bloqueado(['trial_ate' => dataRelativa(5), 'licenca_ate' => null]), 'trial válido por mais 5 dias: liberado');
    assert_igual(false, sistema_bloqueado(['trial_ate' => null, 'licenca_ate' => dataRelativa(0)]), 'licença vence HOJE: ainda liberado (hoje conta como válido)');

    assert_igual(false, sistema_bloqueado(['trial_ate' => null, 'licenca_ate' => dataRelativa(-1)]), 'venceu ontem (carência=3): ainda liberado, dentro da carência');
    assert_igual(false, sistema_bloqueado(['trial_ate' => null, 'licenca_ate' => dataRelativa(-3)]), 'venceu há exatamente 3 dias (limite da carência): ainda liberado');
    assert_igual(true,  sistema_bloqueado(['trial_ate' => null, 'licenca_ate' => dataRelativa(-4)]), 'venceu há 4 dias (passou da carência de 3): bloqueado');

    // trial_ate vencido há muito tempo, mas licenca_ate (mais recente, renovou) ainda válida —
    // usa o MAIOR dos dois, igual licenca_dias_restantes() já fazia antes desta mudança.
    assert_igual(false, sistema_bloqueado(['trial_ate' => dataRelativa(-100), 'licenca_ate' => dataRelativa(10)]), 'trial velho vencido, mas licença paga ainda válida: liberado (usa a mais recente)');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== empresa_em_carencia() — só controla TEXTO do banner, nunca acesso ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    assert_igual(false, empresa_em_carencia([]), 'nunca teve nada: não é "carência", é bloqueio direto');
    assert_igual(false, empresa_em_carencia(['licenca_ate' => dataRelativa(5)]), 'ainda dentro do prazo: não está em carência');
    assert_igual(false, empresa_em_carencia(['licenca_ate' => dataRelativa(0)]), 'vence hoje: ainda não é carência (ainda não venceu de verdade)');

    assert_igual(true, empresa_em_carencia(['licenca_ate' => dataRelativa(-1)]), 'venceu ontem: em carência');
    assert_igual(true, empresa_em_carencia(['licenca_ate' => dataRelativa(-3)]), 'venceu há 3 dias (limite): ainda em carência');
    assert_igual(false, empresa_em_carencia(['licenca_ate' => dataRelativa(-4)]), 'venceu há 4 dias: já passou da carência, não está mais "em carência" (está bloqueada)');

    // Consistência: empresa_em_carencia()=true nunca deve coincidir com sistema_bloqueado()=true,
    // e vice-versa, nos casos vencidos — são estados mutuamente exclusivos por definição.
    foreach ([-1, -2, -3, -4, -5, -10] as $offset) {
        $emp = ['licenca_ate' => dataRelativa($offset)];
        $bloqueado = sistema_bloqueado($emp);
        $carencia  = empresa_em_carencia($emp);
        assert_igual(true, $bloqueado !== $carencia, "offset {$offset} dias: bloqueado e em-carência nunca são true ao mesmo tempo (bloqueado=" . var_export($bloqueado, true) . " carencia=" . var_export($carencia, true) . ")");
    }
}

echo "\n------------------------------------------------------------\n";
echo "$total verificações, $falhas falha(s).\n";
exit($falhas > 0 ? 1 : 0);
