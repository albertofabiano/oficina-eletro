<?php
/*
 * Testes de App\Services\Marketing\SyncService — janela de coleta (7 dias normal, 60 dias na
 * 1ª sincronização da conta, regra inegociável nº1), garantirContaDemo() contra SQLite em
 * memória (SQL portável), e a lógica de mapeamento campanha->insight de syncAccount() via
 * réplica isolada — os upserts de verdade (upsertCampaigns/upsertInsights) usam sintaxe
 * MySQL (`ON DUPLICATE KEY UPDATE`), que o SQLite não entende (confirmado: lança erro de
 * sintaxe), então não dá pra rodar syncAccount() de ponta a ponta sem um MySQL de verdade —
 * mesma limitação de "não há banco de teste no projeto" já documentada em CLAUDE.md.
 * Rodar com: php tests/marketing_sync_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Services/Marketing/Dates.php';
require BASE_PATH . '/app/Services/Marketing/AdPlatformInterface.php';
require BASE_PATH . '/app/Services/Marketing/SyncService.php';

use App\Services\Marketing\SyncService;

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

// ── collectionRange: 1ª coleta (sem last_synced_at) = 60 dias; recoleta = 7 dias ────────────
$primeira = SyncService::collectionRange('2026-09-10', null);
assert_igual(['from' => '2026-07-13', 'to' => '2026-09-10'], $primeira, 'collectionRange: 1ª coleta cobre 60 dias (inclusive) terminando hoje');

$recoleta = SyncService::collectionRange('2026-09-10', '2026-09-09 03:00:00');
assert_igual(['from' => '2026-09-04', 'to' => '2026-09-10'], $recoleta, 'collectionRange: recoleta cobre só 7 dias (inclusive) terminando hoje');

// Virada de ano/mês não deve quebrar a aritmética (Dates::addDays já testado à parte, mas
// syncAccount depende de collectionRange não estourar limite nenhum nessas viradas).
$viradaAno = SyncService::collectionRange('2027-01-02', null);
assert_igual('2026-11-04', $viradaAno['from'], 'collectionRange: 60 dias atrás de 02/01/2027 cai em novembro do ano anterior');

// ── garantirContaDemo(): SQL portável, roda de verdade contra SQLite em memória ─────────────
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE empresas (id INTEGER PRIMARY KEY)");
$db->exec("CREATE TABLE mkt_ad_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    empresa_id INTEGER NOT NULL,
    platform TEXT NOT NULL,
    external_id TEXT NOT NULL,
    name TEXT NOT NULL,
    currency TEXT NOT NULL DEFAULT 'BRL',
    status TEXT NOT NULL DEFAULT 'active',
    last_synced_at TEXT NULL,
    last_sync_error TEXT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (empresa_id, platform, external_id)
)");
$db->exec("INSERT INTO empresas (id) VALUES (1), (2)");

$sync = new SyncService($db);

$conta = $sync->garantirContaDemo(1);
assert_igual('demo_1', $conta['external_id'], 'garantirContaDemo: external_id namespaced pela empresa');
assert_igual('fake', $conta['platform'], 'garantirContaDemo: plataforma é "fake"');
assert_igual('active', $conta['status'], 'garantirContaDemo: nasce ativa');

$contaDeNovo = $sync->garantirContaDemo(1);
assert_igual($conta['id'], $contaDeNovo['id'], 'garantirContaDemo: idempotente, não duplica pra empresa que já tem');

$total2 = (int) $db->query("SELECT COUNT(*) FROM mkt_ad_accounts")->fetchColumn();
assert_igual(1, $total2, 'garantirContaDemo: só existe 1 linha de conta demo pra empresa 1, mesmo chamando 2x');

$contaOutraEmpresa = $sync->garantirContaDemo(2);
assert_verdadeiro($contaOutraEmpresa['id'] !== $conta['id'], 'garantirContaDemo: empresa diferente ganha conta própria, não reaproveita a de outra');

// ── syncAccount(): réplica isolada da lógica de mapeamento campanha->insight ────────────────
// (o próprio método SQL não roda em SQLite, ver comentário do topo — isto replica só a regra
// de negócio real que existe ali: pular insight de campanha desconhecida, nunca derrubar a
// coleta inteira por causa disso, e o formato do resumo devolvido.)
function replicaMapeamentoInsights(array $insights, array $campaignIds): array
{
    $rows = [];
    foreach ($insights as $row) {
        $campaignId = $campaignIds[$row['campaign_external_id']] ?? null;
        if ($campaignId === null) continue;
        $rows[] = $row + ['campaign_id' => $campaignId];
    }
    return $rows;
}

$insightsFicticios = [
    ['campaign_external_id' => 'act_1_tv', 'date' => '2026-09-01', 'spend_cents' => 100, 'impressions' => 1, 'clicks' => 1, 'leads' => 1],
    ['campaign_external_id' => 'act_1_excluida', 'date' => '2026-09-01', 'spend_cents' => 200, 'impressions' => 1, 'clicks' => 1, 'leads' => 0],
];
$mapeados = replicaMapeamentoInsights($insightsFicticios, ['act_1_tv' => 10]);
assert_igual(1, count($mapeados), 'syncAccount (réplica): insight de campanha desconhecida (excluída entre listCampaigns e aqui) é descartado, não quebra');
assert_igual(10, $mapeados[0]['campaign_id'], 'syncAccount (réplica): insight de campanha conhecida ganha o id interno certo');

echo "\n{$total} testes, " . ($total - $falhas) . " OK, {$falhas} falha(s)\n";
exit($falhas > 0 ? 1 : 0);
