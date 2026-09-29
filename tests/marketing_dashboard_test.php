<?php
/*
 * Testes de App\Services\Marketing\Dashboard — período, métricas derivadas, série diária,
 * ranking de campanhas e alertas (seção 7 da especificação). Rodar com:
 *   php tests/marketing_dashboard_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Services/Marketing/Money.php';
require BASE_PATH . '/app/Services/Marketing/Dates.php';
require BASE_PATH . '/app/Services/Marketing/Dashboard.php';

use App\Services\Marketing\Dashboard;

$falhas = 0; $total = 0;
function assert_igual($esperado, $obtido, string $descricao): void
{
    global $falhas, $total;
    $total++;
    if ($esperado === $obtido) { echo "  OK  $descricao\n"; return; }
    $falhas++;
    echo "FALHA $descricao\n      esperado: " . var_export($esperado, true) . "\n      obtido:   " . var_export($obtido, true) . "\n";
}

// ── periodRanges: termina ONTEM, período anterior de mesmo tamanho ──────────
$r = Dashboard::periodRanges(7, '2026-09-10');
assert_igual(['from' => '2026-09-03', 'to' => '2026-09-09'], $r['current'], 'periodRanges(7): atual termina em 09 (ontem)');
assert_igual(['from' => '2026-08-27', 'to' => '2026-09-02'], $r['previous'], 'periodRanges(7): anterior é os 7 dias antes do atual');

// ── sumInsights / deriveMetrics ──────────────────────────────────────────────
$insights = [
    ['campaign_id' => 1, 'date' => '2026-09-01', 'spend_cents' => 1000, 'impressions' => 5000, 'clicks' => 50, 'leads' => 2],
    ['campaign_id' => 1, 'date' => '2026-09-02', 'spend_cents' => 2000, 'impressions' => 5000, 'clicks' => 50, 'leads' => 3],
];
$totais = Dashboard::sumInsights($insights);
assert_igual(['spend_cents' => 3000, 'impressions' => 10000, 'clicks' => 100, 'leads' => 5], $totais, 'sumInsights soma os totais');

$derivadas = Dashboard::deriveMetrics($totais);
assert_igual(600, $derivadas['cost_per_lead_cents'], 'deriveMetrics: custo por lead = 3000/5 = 600');
assert_igual(30, $derivadas['cost_per_click_cents'], 'deriveMetrics: custo por clique = 3000/100 = 30');
assert_igual(300, $derivadas['cpm_cents'], 'deriveMetrics: CPM = 3000*1000/10000 = 300');
assert_igual(0.01, $derivadas['ctr'], 'deriveMetrics: CTR = 100/10000 = 0.01');

$zerado = Dashboard::deriveMetrics(['spend_cents' => 0, 'impressions' => 0, 'clicks' => 0, 'leads' => 0]);
assert_igual(null, $zerado['cost_per_lead_cents'], 'deriveMetrics: 0 leads -> custo por lead null (não divide por zero)');
assert_igual(null, $zerado['ctr'], 'deriveMetrics: 0 impressões -> CTR null');

// ── percentChange ────────────────────────────────────────────────────────────
assert_igual(0.5, Dashboard::percentChange(150, 100), 'percentChange: 150 vs 100 = +50%');
assert_igual(null, Dashboard::percentChange(150, null), 'percentChange: sem base anterior = null');
assert_igual(null, Dashboard::percentChange(150, 0), 'percentChange: base zero = null (evita divisão por zero)');

// ── dailySeries: zero-preenchido nos dias sem dado ──────────────────────────
$serie = Dashboard::dailySeries($insights, ['from' => '2026-09-01', 'to' => '2026-09-03']);
assert_igual(3, count($serie), 'dailySeries: 3 pontos pro intervalo de 3 dias');
assert_igual(['date' => '2026-09-03', 'spend_cents' => 0, 'leads' => 0], $serie[2], 'dailySeries: dia sem dado vem zerado');

// ── campaignRows: maior investimento primeiro ───────────────────────────────
$campanhas = [
    ['id' => 1, 'name' => 'A', 'status' => 'active', 'daily_budget_cents' => 1000],
    ['id' => 2, 'name' => 'B', 'status' => 'active', 'daily_budget_cents' => 1000],
];
$insightsRank = [
    ['campaign_id' => 1, 'date' => '2026-09-01', 'spend_cents' => 100, 'impressions' => 0, 'clicks' => 0, 'leads' => 0],
    ['campaign_id' => 2, 'date' => '2026-09-01', 'spend_cents' => 500, 'impressions' => 0, 'clicks' => 0, 'leads' => 0],
];
$rows = Dashboard::campaignRows($campanhas, $insightsRank);
assert_igual(2, $rows[0]['campaign']['id'], 'campaignRows: campanha B (mais gasto) vem primeiro');
assert_igual(1, $rows[1]['campaign']['id'], 'campaignRows: campanha A (menos gasto) vem depois');

// ── evaluateAlerts ───────────────────────────────────────────────────────────
$rowsAlerta = [
    ['campaign' => ['id' => 10, 'name' => 'Sem lead', 'status' => 'active'], 'metrics' => ['leads' => 0, 'spend_cents' => 6000, 'cost_per_lead_cents' => null]],
    ['campaign' => ['id' => 11, 'name' => 'Sem lead barato', 'status' => 'active'], 'metrics' => ['leads' => 0, 'spend_cents' => 4000, 'cost_per_lead_cents' => null]],
    ['campaign' => ['id' => 12, 'name' => 'CPL alto', 'status' => 'active'], 'metrics' => ['leads' => 1, 'spend_cents' => 3200, 'cost_per_lead_cents' => 3200]],
    ['campaign' => ['id' => 13, 'name' => 'Pausada sem lead', 'status' => 'paused'], 'metrics' => ['leads' => 0, 'spend_cents' => 9000, 'cost_per_lead_cents' => null]],
];
$alertas = Dashboard::evaluateAlerts($rowsAlerta, 2000);
assert_igual(2, count($alertas), 'evaluateAlerts: 2 alertas (crítico + atenção), pausada e "barata" de fora');
assert_igual('critical', $alertas[0]['severity'], 'evaluateAlerts: gasto>=R$50 sem lead é crítico');
assert_igual(10, $alertas[0]['campaign_id'], 'evaluateAlerts: alerta crítico é da campanha 10');
assert_igual('warning', $alertas[1]['severity'], 'evaluateAlerts: CPL 1,5x acima da média é atenção');

echo "\n{$total} testes, " . ($total - $falhas) . " OK, {$falhas} falha(s)\n";
exit($falhas > 0 ? 1 : 0);
