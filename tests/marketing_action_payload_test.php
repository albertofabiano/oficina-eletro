<?php
/*
 * Testes de App\Services\Marketing\ActionPayload — validação do payload de um pedido da fila
 * de aprovação ANTES de usar (o executor chama isso mesmo em modo simulação, regra
 * inegociável: payload ruim tem que falhar mesmo sem chamar a plataforma de verdade).
 * Rodar com: php tests/marketing_action_payload_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Services/Marketing/ActionPayload.php';

use App\Services\Marketing\ActionPayload;

$falhas = 0; $total = 0;
function assert_igual($esperado, $obtido, string $descricao): void
{
    global $falhas, $total;
    $total++;
    if ($esperado === $obtido) { echo "  OK  $descricao\n"; return; }
    $falhas++;
    echo "FALHA $descricao\n      esperado: " . var_export($esperado, true) . "\n      obtido:   " . var_export($obtido, true) . "\n";
}
function assert_lanca(callable $fn, string $descricao): void
{
    global $falhas, $total;
    $total++;
    try { $fn(); $falhas++; echo "FALHA $descricao (esperava exceção, não lançou)\n"; }
    catch (\Throwable) { echo "  OK  $descricao\n"; }
}

assert_igual(['type' => 'pause_campaign'], ActionPayload::parse('pause_campaign', null), 'parse: pause_campaign nunca precisa de payload');
assert_igual(['type' => 'pause_campaign'], ActionPayload::parse('pause_campaign', []), 'parse: pause_campaign com payload vazio (o formato gravado de verdade)');
assert_igual(['type' => 'resume_campaign'], ActionPayload::parse('resume_campaign', null), 'parse: resume_campaign idem');

assert_igual(
    ['type' => 'update_daily_budget', 'to_cents' => 3200, 'from_cents' => 4000],
    ActionPayload::parse('update_daily_budget', ['daily_budget_cents' => 3200, 'previous_daily_budget_cents' => 4000]),
    'parse: update_daily_budget lê to_cents/from_cents do payload'
);
assert_igual(
    ['type' => 'update_daily_budget', 'to_cents' => 3200, 'from_cents' => 3200],
    ActionPayload::parse('update_daily_budget', ['daily_budget_cents' => 3200]),
    'parse: sem previous_daily_budget_cents, cai no próprio to_cents (nunca null)'
);

assert_lanca(fn() => ActionPayload::parse('update_daily_budget', null), 'parse: update_daily_budget sem payload nenhum lança');
assert_lanca(fn() => ActionPayload::parse('update_daily_budget', ['daily_budget_cents' => 0]), 'parse: daily_budget_cents = 0 lança (precisa ser positivo)');
assert_lanca(fn() => ActionPayload::parse('update_daily_budget', ['daily_budget_cents' => -100]), 'parse: daily_budget_cents negativo lança');
assert_lanca(fn() => ActionPayload::parse('update_daily_budget', ['daily_budget_cents' => '3200']), 'parse: daily_budget_cents como string (não int de verdade) lança');
assert_lanca(fn() => ActionPayload::parse('update_daily_budget', ['daily_budget_cents' => 3200, 'previous_daily_budget_cents' => 0]), 'parse: previous_daily_budget_cents = 0 lança');
assert_lanca(fn() => ActionPayload::parse('cancelar_tudo', []), 'parse: action_type desconhecido lança, nunca segue adiante');

assert_igual([], ActionPayload::toStored('pause_campaign', ['ignorado' => 1]), 'toStored: pause_campaign sempre grava {} — extra é ignorado');
assert_igual(
    ['daily_budget_cents' => 3200, 'previous_daily_budget_cents' => 4000],
    ActionPayload::toStored('update_daily_budget', ['daily_budget_cents' => 3200, 'previous_daily_budget_cents' => 4000]),
    'toStored: update_daily_budget grava o extra como veio'
);

echo "\n{$total} testes, " . ($total - $falhas) . " OK, {$falhas} falha(s)\n";
exit($falhas > 0 ? 1 : 0);
