<?php
/*
 * Testes de App\Controllers\MarketingController::normalizarCustomerId() (função pura, sem
 * banco/rede) e App\Services\Marketing\SyncService::contaAtivaOuDemo() (SQL portável, contra
 * SQLite em memória) — ver "Conectar conta real do Google Ads ao módulo Marketing" (tela
 * marketing/conta_google_ads.php). Rodar com: php tests/marketing_conta_google_ads_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Core/Controller.php';
require BASE_PATH . '/app/Controllers/MarketingController.php';
require BASE_PATH . '/app/Services/Marketing/Dates.php';
require BASE_PATH . '/app/Services/Marketing/AdPlatformInterface.php';
require BASE_PATH . '/app/Services/Marketing/SyncService.php';

use App\Controllers\MarketingController;
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

// ── normalizarCustomerId(): só dígitos, aceita qualquer formatação que o Google Ads mostra ──
assert_igual('1234567890', MarketingController::normalizarCustomerId('123-456-7890'), 'normalizarCustomerId: remove traços');
assert_igual('1234567890', MarketingController::normalizarCustomerId('  123 456 7890  '), 'normalizarCustomerId: remove espaços e aparas');
assert_igual('1234567890', MarketingController::normalizarCustomerId('1234567890'), 'normalizarCustomerId: já só-dígitos, sem mudança');
assert_igual('', MarketingController::normalizarCustomerId('abc'), 'normalizarCustomerId: sem nenhum dígito -> vazio');
assert_igual('', MarketingController::normalizarCustomerId(''), 'normalizarCustomerId: entrada vazia -> vazio');

// ── contaAtivaOuDemo(): prefere a conta REAL ativa; cai pra demo se não houver uma ──────────
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
$db->exec("INSERT INTO empresas (id) VALUES (1), (2), (3)");
$sync = new SyncService($db);

// Empresa 1: nunca conectou nada ainda -> cai na demo (criando-a na hora).
$conta1 = $sync->contaAtivaOuDemo(1);
assert_igual('fake', $conta1['platform'], 'contaAtivaOuDemo: sem conta real -> devolve (e cria) a demo');

// Empresa 2: tem demo E uma conta real ativa -> prefere a real, nunca mistura as duas.
$sync->garantirContaDemo(2);
$db->exec("INSERT INTO mkt_ad_accounts (empresa_id, platform, external_id, name, status)
           VALUES (2, 'google_ads', '1112223333', 'Cliente Real Ltda', 'active')");
$conta2 = $sync->contaAtivaOuDemo(2);
assert_igual('google_ads', $conta2['platform'], 'contaAtivaOuDemo: com conta real ativa -> prefere ela, ignora a demo');
assert_igual('1112223333', $conta2['external_id'], 'contaAtivaOuDemo: devolve o Customer ID certo da conta real');

// Empresa 3: tinha conta real, mas foi desconectada (status != active) -> cai de volta pra demo.
$sync->garantirContaDemo(3);
$db->exec("INSERT INTO mkt_ad_accounts (empresa_id, platform, external_id, name, status)
           VALUES (3, 'google_ads', '4445556666', 'Ex-Cliente Ltda', 'disconnected')");
$conta3 = $sync->contaAtivaOuDemo(3);
assert_igual('fake', $conta3['platform'], 'contaAtivaOuDemo: conta real desconectada -> volta pra demo, não usa a desligada');

echo "\n{$total} testes, " . ($total - $falhas) . " OK, {$falhas} falha(s)\n";
exit($falhas > 0 ? 1 : 0);
