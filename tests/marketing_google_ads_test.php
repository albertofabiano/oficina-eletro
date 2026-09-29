<?php
/*
 * Testes de App\Services\Marketing\GoogleAdsPlatform — parsing de resposta da API REST do
 * Google Ads (fixtures no formato real: JSON em camelCase, int64 como string, conversions
 * como número), conversão micros<->centavos sem float, mapeamento de status, e mensagem de
 * erro sanitizada. Nada aqui chama a rede de verdade — os métodos testados são todos
 * estáticos/puros, exatamente pra poderem ser validados sem credencial nenhuma (que ainda não
 * existe — ver CLAUDE.md, "Módulo Marketing"). GoogleOAuthClient é testado só na validação de
 * entrada (client_id/secret vazio), que também não precisa de rede.
 * Rodar com: php tests/marketing_google_ads_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Services/Marketing/Dates.php';
require BASE_PATH . '/app/Services/Marketing/AdPlatformInterface.php';
require BASE_PATH . '/app/Services/Marketing/GoogleAdsPlatform.php';
require BASE_PATH . '/app/Services/Marketing/GoogleOAuthClient.php';

use App\Services\Marketing\GoogleAdsPlatform;
use App\Services\Marketing\GoogleOAuthClient;

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

// ── microsToCents / centsToMicros: nunca passa por float ────────────────────────────────────
assert_igual(4000, GoogleAdsPlatform::microsToCents(40_000_000), 'microsToCents: R$40,00 (40.000.000 micros) = 4000 centavos');
assert_igual(1234, GoogleAdsPlatform::microsToCents(12_340_000), 'microsToCents: R$12,34 = 1234 centavos');
assert_igual(0, GoogleAdsPlatform::microsToCents(0), 'microsToCents: 0 micros = 0 centavos');
assert_igual(1, GoogleAdsPlatform::microsToCents(6_000), 'microsToCents: fração de centavo arredonda (6000 micros = 0,6 centavo -> 1)');
assert_igual(40_000_000, GoogleAdsPlatform::centsToMicros(4000), 'centsToMicros: 4000 centavos = 40.000.000 micros (inverso exato)');

// ── mapStatus: status desconhecido nunca vira "active" por engano ───────────────────────────
assert_igual('active', GoogleAdsPlatform::mapStatus('ENABLED'), 'mapStatus: ENABLED -> active');
assert_igual('paused', GoogleAdsPlatform::mapStatus('PAUSED'), 'mapStatus: PAUSED -> paused');
assert_igual('archived', GoogleAdsPlatform::mapStatus('REMOVED'), 'mapStatus: REMOVED -> archived');
assert_igual('paused', GoogleAdsPlatform::mapStatus('UNKNOWN'), 'mapStatus: status desconhecido cai em paused, nunca active');

// ── parseCampaignsResponse: fixture no formato real da API (camelCase, id como string) ──────
$respostaCampanhas = [
    ['campaign' => ['id' => '111', 'name' => 'Conserto de TV — Leads', 'status' => 'ENABLED'], 'campaignBudget' => ['resourceName' => 'customers/999/campaignBudgets/222', 'amountMicros' => '40000000']],
    ['campaign' => ['id' => '333', 'name' => 'Remarketing', 'status' => 'PAUSED'], 'campaignBudget' => ['resourceName' => 'customers/999/campaignBudgets/444', 'amountMicros' => '15000000']],
    ['campaign' => ['id' => '555', 'name' => 'Sem orçamento próprio (grupo compartilhado)', 'status' => 'ENABLED']], // sem campaignBudget
];
$campanhas = GoogleAdsPlatform::parseCampaignsResponse($respostaCampanhas);
assert_igual(3, count($campanhas), 'parseCampaignsResponse: 3 linhas viram 3 campanhas');
assert_igual('111', $campanhas[0]['external_id'], 'parseCampaignsResponse: external_id é o campaign.id (string), sem prefixo');
assert_igual('active', $campanhas[0]['status'], 'parseCampaignsResponse: ENABLED mapeado pra active');
assert_igual(4000, $campanhas[0]['daily_budget_cents'], 'parseCampaignsResponse: orçamento em micros convertido pra centavos');
assert_igual(null, $campanhas[2]['daily_budget_cents'], 'parseCampaignsResponse: sem campaignBudget na resposta -> null (orçamento no grupo, não na campanha)');

// ── parseInsightsResponse: conversions vem como NÚMERO (não string) no JSON real ────────────
$respostaInsights = [
    ['campaign' => ['id' => '111'], 'segments' => ['date' => '2026-09-01'], 'metrics' => ['costMicros' => '12340000', 'impressions' => '5000', 'clicks' => '50', 'conversions' => 2.0]],
    ['campaign' => ['id' => '333'], 'segments' => ['date' => '2026-09-01'], 'metrics' => ['costMicros' => '0', 'impressions' => '0', 'clicks' => '0', 'conversions' => 0.0]],
    ['campaign' => ['id' => '111'], 'segments' => ['date' => '2026-09-02'], 'metrics' => ['costMicros' => '9990000', 'impressions' => '4000', 'clicks' => '40', 'conversions' => 1.6]],
];
$insights = GoogleAdsPlatform::parseInsightsResponse($respostaInsights);
assert_igual(3, count($insights), 'parseInsightsResponse: 3 linhas viram 3 inserções diárias');
assert_igual('111', $insights[0]['campaign_external_id'], 'parseInsightsResponse: campaign_external_id vem de campaign.id');
assert_igual('2026-09-01', $insights[0]['date'], 'parseInsightsResponse: date vem de segments.date');
assert_igual(1234, $insights[0]['spend_cents'], 'parseInsightsResponse: costMicros convertido pra centavos');
assert_igual(5000, $insights[0]['impressions'], 'parseInsightsResponse: impressions convertido de string pra int');
assert_igual(2, $insights[0]['leads'], 'parseInsightsResponse: conversions (float) arredondado pra "leads" inteiro');
assert_igual(2, $insights[2]['leads'], 'parseInsightsResponse: 1.6 conversions arredonda pra 2 leads (não trunca pra 1)');

// ── describeError: extrai mensagem sem nunca vazar token/header ────────────────────────────
$erroPermissao = ['error' => ['code' => 403, 'message' => 'Request had insufficient authentication scopes.']];
assert_igual('Request had insufficient authentication scopes.', GoogleAdsPlatform::describeError($erroPermissao), 'describeError: mensagem simples extraída direto');

$erroComDetalhe = [
    'error' => [
        'code' => 400, 'message' => 'Request contains an invalid argument.',
        'details' => [[
            '@type' => 'type.googleapis.com/google.ads.googleads.v18.errors.GoogleAdsFailure',
            'errors' => [['errorCode' => ['authorizationError' => 'USER_PERMISSION_DENIED'], 'message' => 'The user does not have permission to access customer.']],
        ]],
    ],
];
assert_igual(
    'Request contains an invalid argument. — The user does not have permission to access customer.',
    GoogleAdsPlatform::describeError($erroComDetalhe),
    'describeError: junta a mensagem principal com o detalhe do GoogleAdsFailure'
);
assert_igual('Erro desconhecido na API do Google Ads.', GoogleAdsPlatform::describeError([]), 'describeError: resposta sem "error" nenhum cai num texto genérico, não quebra');

// ── construção exige config completa (nunca segue com token/id vazio) ──────────────────────
assert_lanca(fn() => new GoogleOAuthClient('', 'segredo'), 'GoogleOAuthClient: client_id vazio lança');
assert_lanca(fn() => new GoogleOAuthClient('id', ''), 'GoogleOAuthClient: client_secret vazio lança');
assert_lanca(function () {
    $oauth = new GoogleOAuthClient('id-valido', 'segredo-valido');
    new GoogleAdsPlatform('', '1234567890', 'refresh-token-fake', $oauth);
}, 'GoogleAdsPlatform: developer_token vazio lança');
assert_lanca(function () {
    $oauth = new GoogleOAuthClient('id-valido', 'segredo-valido');
    new GoogleAdsPlatform('dev-token', '', 'refresh-token-fake', $oauth);
}, 'GoogleAdsPlatform: login_customer_id vazio lança');
assert_lanca(function () {
    // refresh_token vazio não impede construir a classe (é normal antes de a conta ser
    // conectada) — a checagem acontece só na hora de tentar usar, dentro de GoogleOAuthClient,
    // e isso já basta pra nunca chegar a abrir uma conexão de rede com token nenhum.
    $oauth = new GoogleOAuthClient('id-valido', 'segredo-valido');
    $plat = new GoogleAdsPlatform('dev-token', '1234567890', '', $oauth);
    $plat->listCampaigns('1234567890');
}, 'GoogleAdsPlatform: refresh_token vazio lança ao tentar usar, antes de qualquer chamada de rede');

echo "\n{$total} testes, " . ($total - $falhas) . " OK, {$falhas} falha(s)\n";
exit($falhas > 0 ? 1 : 0);
