<?php
/*
 * Testes de App\Services\Marketing\Rules — as 3 regras de otimização (seção 8 da
 * especificação): pausar sem lead > reduzir orçamento com CPL alto > aumentar orçamento com
 * CPL baixo, no máximo 1 sugestão por campanha, a primeira que casar vence. Rodar com:
 *   php tests/marketing_rules_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Services/Marketing/Money.php';
require BASE_PATH . '/app/Services/Marketing/Rules.php';

use App\Services\Marketing\Rules;

$falhas = 0; $total = 0;
function assert_igual($esperado, $obtido, string $descricao): void
{
    global $falhas, $total;
    $total++;
    if ($esperado === $obtido) { echo "  OK  $descricao\n"; return; }
    $falhas++;
    echo "FALHA $descricao\n      esperado: " . var_export($esperado, true) . "\n      obtido:   " . var_export($obtido, true) . "\n";
}

function linha(int $id, string $status, ?int $orcamento, int $gasto, int $leads, ?int $cpl): array
{
    return [
        'campaign' => ['id' => $id, 'name' => "Campanha {$id}", 'status' => $status, 'daily_budget_cents' => $orcamento],
        'metrics'  => ['spend_cents' => $gasto, 'leads' => $leads, 'cost_per_lead_cents' => $cpl],
    ];
}

// ── pause-no-leads: 0 leads + gasto >= R$50 ─────────────────────────────────────────────────
$r1 = Rules::suggest([linha(1, 'active', 4000, 6240, 0, null)], null, 7);
assert_igual(1, count($r1), 'pause-no-leads: dispara com 0 leads e gasto >= R$50');
assert_igual('pause-no-leads', $r1[0]['rule_id'], 'pause-no-leads: rule_id certo');
assert_igual('pause_campaign', $r1[0]['action_type'], 'pause-no-leads: action_type certo');
assert_igual([], $r1[0]['payload'], 'pause-no-leads: payload vazio (não precisa de dado extra)');
assert_igual('Gastou R$ 62,40 nos últimos 7 dias sem gerar nenhum lead.', $r1[0]['reason'], 'pause-no-leads: texto do motivo em pt-BR, exemplo da especificação');

$r2 = Rules::suggest([linha(2, 'active', 4000, 4999, 0, null)], null, 7);
assert_igual(0, count($r2), 'pause-no-leads: NÃO dispara com gasto abaixo de R$50 (4999 centavos)');

$r3 = Rules::suggest([linha(3, 'paused', 4000, 9000, 0, null)], null, 7);
assert_igual(0, count($r3), 'pause-no-leads: campanha pausada nunca gera sugestão nenhuma');

// ── reduce-budget-high-cpl: CPL > 1,5x a média da conta ─────────────────────────────────────
$r4 = Rules::suggest([linha(4, 'active', 4000, 3800, 1, 3800)], 2000, 7);
assert_igual(1, count($r4), 'reduce-budget-high-cpl: dispara com CPL > 1,5x a média (3800 > 3000)');
assert_igual('reduce-budget-high-cpl', $r4[0]['rule_id'], 'reduce-budget-high-cpl: rule_id certo');
assert_igual(['daily_budget_cents' => 3200, 'previous_daily_budget_cents' => 4000], $r4[0]['payload'], 'reduce-budget-high-cpl: corta 20% (4000*0,8=3200)');
assert_igual('Custo por lead de R$ 38,00, acima de 1,5x a média da conta (R$ 20,00).', $r4[0]['reason'], 'reduce-budget-high-cpl: texto do motivo, exemplo exato da especificação');

// nunca sugere um corte que ficaria MAIOR ou igual ao orçamento atual (piso de R$5 aplicado)
$r5 = Rules::suggest([linha(5, 'active', 600, 570, 1, 570)], 300, 7);
// budget=600 -> 600*0.8=480, max(500,480)=500 < 600, então deveria disparar normalmente
assert_igual(1, count($r5), 'reduce-budget-high-cpl: piso de R$5,00 aplicado (500 < 600, ainda dispara)');
assert_igual(500, $r5[0]['payload']['daily_budget_cents'], 'reduce-budget-high-cpl: nunca sugere abaixo de R$5,00 (piso min_daily_budget)');

$r6 = Rules::suggest([linha(6, 'active', 500, 480, 1, 480)], 300, 7);
// budget=500 (já no piso) -> 500*0.8=400, max(500,400)=500, 500 < 500 é falso -> não dispara
assert_igual(0, count($r6), 'reduce-budget-high-cpl: orçamento já no piso mínimo não gera sugestão (ficaria igual, não menor)');

// ── increase-budget-low-cpl: >=5 leads, CPL <= 0,6x a média, uso do orçamento >= 80% ────────
$linhaAumento = linha(7, 'active', 1000, 5600, 6, 933); // 1000*7=7000 (semana); 5600/7000=0,8 (80%)
$r7 = Rules::suggest([$linhaAumento], 2000, 7);
assert_igual(1, count($r7), 'increase-budget-low-cpl: dispara com >=5 leads, CPL baixo e uso do orçamento >= 80%');
assert_igual('increase-budget-low-cpl', $r7[0]['rule_id'], 'increase-budget-low-cpl: rule_id certo');
assert_igual(1200, $r7[0]['payload']['daily_budget_cents'], 'increase-budget-low-cpl: aumenta 20% (1000*1,2=1200)');

$r8 = Rules::suggest([linha(8, 'active', 1000, 5600, 4, 933)], 2000, 7); // só 4 leads, exige >=5
assert_igual(0, count($r8), 'increase-budget-low-cpl: NÃO dispara com menos de 5 leads');

$r9 = Rules::suggest([linha(9, 'active', 1000, 2000, 6, 333)], 2000, 7); // uso do orçamento baixo (2000/7000=28%)
assert_igual(0, count($r9), 'increase-budget-low-cpl: NÃO dispara se o uso do orçamento está abaixo de 80%');

// ── no máximo 1 sugestão por campanha — pausar vence sobre as outras se as 2 casassem ───────
// (uma campanha com 0 leads e gasto alto sempre cai em pause-no-leads antes de chegar nas
// regras de orçamento, mesmo que o resto dos números também bateria alguma delas)
$linhaAmbigua = linha(10, 'active', 4000, 6000, 0, null);
$r10 = Rules::suggest([$linhaAmbigua], 100, 7);
assert_igual(1, count($r10), 'ordem das regras: só 1 sugestão mesmo quando mais de uma condição po deria bater');
assert_igual('pause-no-leads', $r10[0]['rule_id'], 'ordem das regras: pausar sempre vence (é checada primeiro)');

// ── sem orçamento diário conhecido (fica no conjunto de anúncios) ou sem CPL da conta ───────
$r11 = Rules::suggest([linha(11, 'active', null, 3800, 1, 3800)], 2000, 7);
assert_igual(0, count($r11), 'sem orçamento diário conhecido (null): nunca sugere mudança de orçamento');

$r12 = Rules::suggest([linha(12, 'active', 4000, 3800, 1, 3800)], null, 7);
assert_igual(0, count($r12), 'sem CPL da conta (conta inteira sem lead ainda): nunca sugere mudança de orçamento');

echo "\n{$total} testes, " . ($total - $falhas) . " OK, {$falhas} falha(s)\n";
exit($falhas > 0 ? 1 : 0);
