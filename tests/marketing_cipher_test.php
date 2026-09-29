<?php
/*
 * Testes de App\Services\Marketing\CredentialCipher — round-trip encrypt/decrypt (regra
 * inegociável nº3: token nunca em texto puro no banco), falha com chave errada, e que
 * chave/versão vêm de config/marketing.php sem cair num default inseguro. Rodar com:
 *   php tests/marketing_cipher_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Services/Marketing/CredentialCipher.php';

use App\Services\Marketing\CredentialCipher;

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
    catch (\Throwable) { echo "  OK  $descricao\n"; }
}

$chaveA = base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
$chaveB = base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));

// ── round-trip: o mesmo cifrador decifra o que ele mesmo cifrou ─────────────────────────────
$cifrador = new CredentialCipher(['encryption_key' => $chaveA, 'key_version' => 3]);
$token = 'EAAG_token_de_acesso_da_meta_bem_secreto_123';
$cifrado = $cifrador->encrypt($token);

assert_verdadeiro($cifrado['ciphertext'] !== $token, 'encrypt: ciphertext não é o texto puro (não é passthrough)');
assert_verdadeiro(!str_contains($cifrado['ciphertext'], $token), 'encrypt: ciphertext não contém o token em texto puro embutido');
assert_igual(3, $cifrado['key_version'], 'encrypt: carrega a versão da chave configurada, pra rotação futura');
assert_igual(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, strlen($cifrado['nonce']), 'encrypt: nonce tem o tamanho exato exigido pelo secretbox (24 bytes)');

$decifrado = $cifrador->decrypt($cifrado['ciphertext'], $cifrado['nonce']);
assert_igual($token, $decifrado, 'decrypt: round-trip recupera o token original exatamente');

// ── nonce nunca se repete entre duas chamadas (senão XSalsa20 perde a garantia de sigilo) ───
$cifrado2 = $cifrador->encrypt($token);
assert_verdadeiro($cifrado['nonce'] !== $cifrado2['nonce'], 'encrypt: nonce é diferente a cada chamada, mesmo cifrando o mesmo texto');
assert_verdadeiro($cifrado['ciphertext'] !== $cifrado2['ciphertext'], 'encrypt: ciphertext também difere (nonce diferente muda a saída)');

// ── chave errada nunca decifra (nem lixo, nem parcial) — sempre lança ───────────────────────
$cifradorErrado = new CredentialCipher(['encryption_key' => $chaveB, 'key_version' => 1]);
assert_lanca(fn() => $cifradorErrado->decrypt($cifrado['ciphertext'], $cifrado['nonce']), 'decrypt: chave errada lança exceção, nunca devolve texto adulterado');

// ── ciphertext/nonce corrompido também lança (autenticação do secretbox pegando adulteração) ─
$corrompido = $cifrado['ciphertext'];
$corrompido[0] = $corrompido[0] === "\x00" ? "\x01" : "\x00";
assert_lanca(fn() => $cifrador->decrypt($corrompido, $cifrado['nonce']), 'decrypt: ciphertext adulterado lança exceção (autenticação do secretbox)');

// ── configuração ausente/inválida nunca cai num default silencioso e inseguro ───────────────
assert_lanca(fn() => new CredentialCipher(['encryption_key' => '']), 'construct: chave vazia lança, nunca segue com uma chave "vazia"');
assert_lanca(fn() => new CredentialCipher([]), 'construct: config sem encryption_key nenhuma lança');
assert_lanca(fn() => new CredentialCipher(['encryption_key' => base64_encode('curta_demais')]), 'construct: chave com menos de 32 bytes (decodificada) lança');
assert_lanca(fn() => new CredentialCipher(['encryption_key' => 'não-é-base64-válido!!!']), 'construct: chave que não decodifica certo em base64 lança');

// ── key_version tem um default sensato quando a config não especifica ───────────────────────
$semVersao = new CredentialCipher(['encryption_key' => $chaveA]);
assert_igual(1, $semVersao->keyVersion, 'construct: key_version default é 1 quando a config não especifica');

echo "\n{$total} testes, " . ($total - $falhas) . " OK, {$falhas} falha(s)\n";
exit($falhas > 0 ? 1 : 0);
