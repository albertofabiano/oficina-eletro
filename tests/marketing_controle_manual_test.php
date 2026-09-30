<?php
/*
 * Testes do controle manual de campanha (MarketingController::atualizarStatusCampanha()/
 * atualizarOrcamentoCampanha()) — réplica isolada da query de segurança
 * (carregarCampanhaAtiva(), privada, mesmo formato de JOIN de QueueService::carregarAlvo())
 * contra SQLite em memória, e da conversão de orçamento (moeda_float() + validação > 0).
 * Não instancia o Controller de verdade (json()/csrf_verify() exigem sessão/exit — mesma
 * limitação de sempre pra testar controller isolado neste projeto, ver CLAUDE.md); a query em
 * si é idêntica à testada aqui, só copiada pro controller.
 * Rodar com: php tests/marketing_controle_manual_test.php
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

/** Réplica exata da query privada MarketingController::carregarCampanhaAtiva(). */
function carregarCampanhaAtiva(PDO $db, int $campaignId, int $empresaId): ?array
{
    $stmt = $db->prepare(
        "SELECT c.external_id AS campaign_external_id, c.name AS campaign_name, a.id AS ad_account_id,
                a.platform, a.external_id AS account_external_id, a.empresa_id
         FROM mkt_campaigns c JOIN mkt_ad_accounts a ON a.id = c.ad_account_id
         WHERE c.id = ? AND c.empresa_id = ? AND a.status = 'active'"
    );
    $stmt->execute([$campaignId, $empresaId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE mkt_ad_accounts (id INTEGER PRIMARY KEY, empresa_id INTEGER, platform TEXT, external_id TEXT, status TEXT)');
$db->exec('CREATE TABLE mkt_campaigns (id INTEGER PRIMARY KEY, empresa_id INTEGER, ad_account_id INTEGER, external_id TEXT, name TEXT, status TEXT)');

// Empresa 1: conta real ATIVA (id=1) com 1 campanha (id=10); conta antiga DESCONECTADA (id=2)
// com 1 campanha (id=20) — cenário real de produção (demo desativada ao conectar a real).
// Empresa 2: conta ativa própria (id=3) com campanha id=30 — nunca deve aparecer pra empresa 1.
$db->exec("INSERT INTO mkt_ad_accounts (id, empresa_id, platform, external_id, status) VALUES
    (1, 1, 'google_ads', '8890887611', 'active'),
    (2, 1, 'fake', 'demo_1', 'disconnected'),
    (3, 2, 'google_ads', '9998887777', 'active')");
$db->exec("INSERT INTO mkt_campaigns (id, empresa_id, ad_account_id, external_id, name, status) VALUES
    (10, 1, 1, '111', 'Conserto de TV - 1', 'active'),
    (20, 1, 2, '222', 'Campanha demo antiga', 'active'),
    (30, 2, 3, '333', 'Campanha de outra empresa', 'active')");

$real = carregarCampanhaAtiva($db, 10, 1);
assert_igual(true, $real !== null, 'carregarCampanhaAtiva: campanha da conta ativa da própria empresa é encontrada');
assert_igual('111', $real['campaign_external_id'] ?? null, 'carregarCampanhaAtiva: campaign_external_id certo');
assert_igual('8890887611', $real['account_external_id'] ?? null, 'carregarCampanhaAtiva: account_external_id certo (da conta ativa)');
assert_igual('google_ads', $real['platform'] ?? null, 'carregarCampanhaAtiva: platform certo, pronto pro PlatformFactory::make()');

$antiga = carregarCampanhaAtiva($db, 20, 1);
assert_igual(null, $antiga, 'carregarCampanhaAtiva: campanha de conta DESCONECTADA nunca é encontrada (evita mandar comando pra conta errada/antiga)');

$outraEmpresa = carregarCampanhaAtiva($db, 30, 1);
assert_igual(null, $outraEmpresa, 'carregarCampanhaAtiva: campanha de OUTRA empresa nunca é encontrada, mesmo pedindo o id certo (isolamento de segurança)');

$idInexistente = carregarCampanhaAtiva($db, 999, 1);
assert_igual(null, $idInexistente, 'carregarCampanhaAtiva: campaign_id inexistente -> null, não quebra');

// ── Conversão/validação de orçamento (mesma lógica de atualizarOrcamentoCampanha()) ──────────
function centavosOrcamento(string $valorPost): int
{
    return (int) round(moeda_float($valorPost) * 100);
}
assert_igual(5000, centavosOrcamento('50,00'), 'orçamento: "50,00" -> 5000 centavos');
assert_igual(5000, centavosOrcamento('50'), 'orçamento: "50" (sem decimal) -> 5000 centavos');
assert_igual(12345, centavosOrcamento('123,45'), 'orçamento: "123,45" -> 12345 centavos');
assert_igual(0, centavosOrcamento('abc'), 'orçamento: texto sem número -> 0 (rejeitado pelo controller, > 0 exigido)');
assert_igual(0, centavosOrcamento('0'), 'orçamento: "0" -> 0 centavos (rejeitado, > 0 exigido)');
assert_igual(0, centavosOrcamento(''), 'orçamento: vazio -> 0 (rejeitado)');

echo "\n{$total} testes, " . ($total - $falhas) . " OK, {$falhas} falha(s)\n";
exit($falhas > 0 ? 1 : 0);
