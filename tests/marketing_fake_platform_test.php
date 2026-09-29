<?php
/*
 * Testes de App\Services\Marketing\FakeAdPlatform — determinismo (mesma seed sempre gera o
 * mesmo número, sem gravar estado), formato dos dados e os 4 perfis pensados pra cada regra
 * de alerta/sugestão ter algo pra achar. Rodar com: php tests/marketing_fake_platform_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Services/Marketing/AdPlatformInterface.php';
require BASE_PATH . '/app/Services/Marketing/Dates.php';
require BASE_PATH . '/app/Services/Marketing/FakeAdPlatform.php';

use App\Services\Marketing\FakeAdPlatform;

$falhas = 0; $total = 0;
function assert_igual($esperado, $obtido, string $descricao): void
{
    global $falhas, $total;
    $total++;
    if ($esperado === $obtido) { echo "  OK  $descricao\n"; return; }
    $falhas++;
    echo "FALHA $descricao\n      esperado: " . var_export($esperado, true) . "\n      obtido:   " . var_export($obtido, true) . "\n";
}
function assert_verdadeiro(bool $cond, string $descricao): void
{
    global $falhas, $total;
    $total++;
    if ($cond) { echo "  OK  $descricao\n"; return; }
    $falhas++;
    echo "FALHA $descricao\n";
}

$plat = new FakeAdPlatform();

// ── noise(): determinístico e em [0, 1) ──────────────────────────────────────
$n1 = FakeAdPlatform::noise('act_123:tv:2026-09-01');
$n2 = FakeAdPlatform::noise('act_123:tv:2026-09-01');
assert_igual($n1, $n2, 'noise: mesma seed sempre gera o mesmo número');
assert_verdadeiro($n1 >= 0 && $n1 < 1, 'noise: resultado está em [0, 1)');
assert_verdadeiro(FakeAdPlatform::noise('a') !== FakeAdPlatform::noise('b'), 'noise: seeds diferentes geram números diferentes');

// ── listCampaigns: 4 campanhas, external_id namespaced pela conta ───────────
$campanhas = $plat->listCampaigns('act_999');
assert_igual(4, count($campanhas), 'listCampaigns: 4 perfis de campanha');
$externos = array_column($campanhas, 'external_id');
assert_verdadeiro(in_array('act_999_tv', $externos, true), 'listCampaigns: external_id prefixado pela conta (tv)');
assert_verdadeiro(in_array('act_999_rmk', $externos, true), 'listCampaigns: external_id prefixado pela conta (rmk)');

$porNome = [];
foreach ($campanhas as $c) $porNome[$c['name']] = $c;
assert_igual('active', $porNome['Conserto de TV — Leads']['status'], 'listCampaigns: TV é ativa (perfil bom, vira increase-budget)');
assert_igual('paused', $porNome['Remarketing — site']['status'], 'listCampaigns: Remarketing é pausada de propósito');

// ── getDailyInsights: campanha pausada nunca gasta, campanha "gel" nunca gera lead ──
$insights = $plat->getDailyInsights('act_999', '2026-09-01', '2026-09-07');
assert_igual(4 * 7, count($insights), 'getDailyInsights: 4 campanhas x 7 dias = 28 linhas');

$totaisPorCampanha = [];
foreach ($insights as $row) {
    $totaisPorCampanha[$row['campaign_external_id']]['spend'] = ($totaisPorCampanha[$row['campaign_external_id']]['spend'] ?? 0) + $row['spend_cents'];
    $totaisPorCampanha[$row['campaign_external_id']]['leads'] = ($totaisPorCampanha[$row['campaign_external_id']]['leads'] ?? 0) + $row['leads'];
}
assert_igual(0, $totaisPorCampanha['act_999_rmk']['spend'], 'getDailyInsights: campanha pausada nunca gasta nada');
assert_igual(0, $totaisPorCampanha['act_999_gel']['leads'], 'getDailyInsights: perfil "gel" (lead_rate=0) nunca gera lead — dispara pause-no-leads');
assert_verdadeiro($totaisPorCampanha['act_999_gel']['spend'] > 5000, 'getDailyInsights: "gel" gasta o bastante em 7 dias pra cruzar o piso de R$50 do pause-no-leads');
assert_verdadeiro($totaisPorCampanha['act_999_tv']['leads'] > 0, 'getDailyInsights: perfil "tv" gera lead de verdade');

// ── setCampaignStatus/setDailyBudget: valida entrada, campanha inexistente falha ────
try {
    $plat->setCampaignStatus('act_999', 'nao-existe', 'paused');
    $falhas++; $total++; echo "FALHA setCampaignStatus com campanha inexistente deveria lançar\n";
} catch (\Throwable) { $total++; echo "  OK  setCampaignStatus: campanha inexistente lança exceção\n"; }

try {
    $plat->setDailyBudget('act_999', 'act_999_tv', 0);
    $falhas++; $total++; echo "FALHA setDailyBudget com orçamento 0 deveria lançar\n";
} catch (\Throwable) { $total++; echo "  OK  setDailyBudget: orçamento <= 0 lança exceção\n"; }

echo "\n{$total} testes, " . ($total - $falhas) . " OK, {$falhas} falha(s)\n";
exit($falhas > 0 ? 1 : 0);
