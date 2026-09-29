<?php

namespace App\Services\Marketing;

/**
 * Criptografia autenticada do token de acesso das plataformas de anúncio
 * (`mkt_credentials.token_ciphertext`/`token_nonce`) — regra inegociável nº3 do documento
 * de especificação. Usa sodium_crypto_secretbox (XSalsa20-Poly1305, nativo do PHP desde a
 * 7.2, sem dependência nova). A chave nunca fica no banco — vive em config/marketing.php
 * (gitignored), fora do Git.
 *
 * Nenhuma tela ou endpoint deve chamar decrypt() fora do serviço de coleta/execução —
 * o token descriptografado só existe em memória, pelo tempo mínimo necessário pra chamar
 * a API da plataforma.
 */
class CredentialCipher
{
    private string $key;
    public readonly int $keyVersion;

    public function __construct(?array $config = null)
    {
        $config ??= self::loadConfig();
        $keyB64 = (string) ($config['encryption_key'] ?? '');
        if ($keyB64 === '') {
            throw new \RuntimeException('MKT_ENCRYPTION_KEY ausente em config/marketing.php.');
        }
        $key = base64_decode($keyB64, true);
        if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new \RuntimeException('Chave de criptografia do Marketing inválida — precisa ter 32 bytes em base64.');
        }
        $this->key = $key;
        $this->keyVersion = (int) ($config['key_version'] ?? 1);
    }

    private static function loadConfig(): array
    {
        $arquivo = BASE_PATH . '/config/marketing.php';
        return is_file($arquivo) ? require $arquivo : [];
    }

    /** @return array{ciphertext:string, nonce:string, key_version:int} prontos pra gravar em BLOB/BINARY. */
    public function encrypt(string $plaintext): array
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $this->key);
        return ['ciphertext' => $ciphertext, 'nonce' => $nonce, 'key_version' => $this->keyVersion];
    }

    public function decrypt(string $ciphertext, string $nonce): string
    {
        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->key);
        if ($plaintext === false) {
            throw new \RuntimeException('Falha ao descriptografar credencial — chave errada ou dado corrompido.');
        }
        return $plaintext;
    }
}
