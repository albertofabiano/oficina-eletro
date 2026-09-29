<?php
/*
 * Testes de App\Services\Marketing\PlatformFactory — resolve a classe certa por
 * mkt_ad_accounts.platform, busca/decifra a credencial global do Google Ads em
 * mkt_credentials (SQL portável, roda contra SQLite em memória — diferente de SyncService,
 * aqui é só SELECT simples, sem upsert MySQL-específico), e nunca segue adiante com token
 * vazio quando a conta ainda não foi conectada. Rodar com: php tests/marketing_platform_factory_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Services/Marketing/Dates.php';
require BASE_PATH . '/app/Services/Marketing/AdPlatformInterface.php';
require BASE_PATH . '/app/Services/Marketing/FakeAdPlatform.php';
require BASE_PATH . '/app/Services/Marketing/CredentialCipher.php';
require BASE_PATH . '/app/Services/Marketing/GoogleOAuthClient.php';
require BASE_PATH . '/app/Services/Marketing/GoogleAdsPlatform.php';
require BASE_PATH . '/app/Services/Marketing/PlatformFactory.php';

use App\Services\Marketing\CredentialCipher;
use App\Services\Marketing\FakeAdPlatform;
use App\Services\Marketing\GoogleAdsPlatform;
use App\Services\Marketing\PlatformFactory;

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
function assert_lanca(callable $fn, string $descricao): void
{
    global $falhas, $total;
    $total++;
    try { $fn(); $falhas++; echo "FALHA $descricao (esperava exceção, não lançou)\n"; }
    catch (\Throwable $e) { echo "  OK  $descricao\n"; }
}

$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE mkt_credentials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    scope TEXT NOT NULL,
    empresa_id INTEGER NULL,
    platform TEXT NOT NULL,
    token_ciphertext BLOB NOT NULL,
    token_nonce BLOB NOT NULL,
    key_version INTEGER NOT NULL DEFAULT 1
)");

$chave = base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
$configFake = [
    'encryption_key' => $chave,
    'key_version'    => 1,
    'google_ads'     => [
        'client_id'         => 'id-de-teste',
        'client_secret'     => 'segredo-de-teste',
        'developer_token'   => 'dev-token-de-teste',
        'login_customer_id' => '1112223333',
        'api_version'       => 'v18',
    ],
];

// ── 'fake': não depende de banco nem de config nenhuma ──────────────────────────────────────
$plataformaFake = PlatformFactory::make($db, ['platform' => 'fake'], $configFake);
assert_verdadeiro($plataformaFake instanceof FakeAdPlatform, "make('fake'): devolve um FakeAdPlatform");

// ── 'google_ads' sem nenhuma linha em mkt_credentials: erro claro, não token vazio ──────────
assert_lanca(
    fn() => PlatformFactory::make($db, ['platform' => 'google_ads'], $configFake),
    "make('google_ads'): sem credencial gravada ainda, lança em vez de seguir com token vazio"
);

// ── 'google_ads' com credencial gravada: decifra e monta a plataforma de verdade ────────────
$cifrador = new CredentialCipher($configFake);
$cifrado = $cifrador->encrypt('refresh-token-bem-secreto-da-conta-gerenciadora');
$ins = $db->prepare('INSERT INTO mkt_credentials (scope, platform, token_ciphertext, token_nonce, key_version) VALUES (?, ?, ?, ?, ?)');
$ins->bindValue(1, 'global');
$ins->bindValue(2, 'google_ads');
$ins->bindValue(3, $cifrado['ciphertext'], PDO::PARAM_LOB);
$ins->bindValue(4, $cifrado['nonce'], PDO::PARAM_LOB);
$ins->bindValue(5, $cifrado['key_version']);
$ins->execute();

$plataformaGoogle = PlatformFactory::make($db, ['platform' => 'google_ads'], $configFake);
assert_verdadeiro($plataformaGoogle instanceof GoogleAdsPlatform, "make('google_ads'): com credencial gravada, devolve um GoogleAdsPlatform");
assert_igual('google_ads', $plataformaGoogle->id(), "make('google_ads'): id() confirma a plataforma certa");

// ── credencial de OUTRA plataforma (ex.: meta) nunca é usada por engano pro Google Ads ──────
$db->exec("DELETE FROM mkt_credentials");
$ins2 = $db->prepare('INSERT INTO mkt_credentials (scope, platform, token_ciphertext, token_nonce, key_version) VALUES (?, ?, ?, ?, ?)');
$ins2->bindValue(1, 'global');
$ins2->bindValue(2, 'meta');
$ins2->bindValue(3, $cifrado['ciphertext'], PDO::PARAM_LOB);
$ins2->bindValue(4, $cifrado['nonce'], PDO::PARAM_LOB);
$ins2->bindValue(5, 1);
$ins2->execute();
assert_lanca(
    fn() => PlatformFactory::make($db, ['platform' => 'google_ads'], $configFake),
    "make('google_ads'): só existe credencial de 'meta' gravada -> continua sem achar a do Google Ads, lança"
);

// ── 'meta' e plataforma desconhecida: erro claro, nada de fallback silencioso ───────────────
assert_lanca(fn() => PlatformFactory::make($db, ['platform' => 'meta'], $configFake), "make('meta'): ainda não implementada, lança mensagem clara");
assert_lanca(fn() => PlatformFactory::make($db, ['platform' => 'bling'], $configFake), "make('bling'): plataforma desconhecida lança, não tenta adivinhar");

echo "\n{$total} testes, " . ($total - $falhas) . " OK, {$falhas} falha(s)\n";
exit($falhas > 0 ? 1 : 0);
