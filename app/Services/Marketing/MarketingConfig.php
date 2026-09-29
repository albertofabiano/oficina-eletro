<?php

namespace App\Services\Marketing;

/**
 * Leitura compartilhada de config/marketing.php pra quem só precisa do 'dry_run' (o resto do
 * arquivo — chave de criptografia, credenciais do Google Ads — já é lido por conta própria em
 * CredentialCipher/PlatformFactory, cada um só com o pedaço que usa).
 */
class MarketingConfig
{
    public static function load(): array
    {
        $arquivo = BASE_PATH . '/config/marketing.php';
        return is_file($arquivo) ? require $arquivo : [];
    }

    /** Padrão TRUE por decisão consciente — só desliga depois de validar sugestões reais com o dono do produto. */
    public static function isDryRun(): bool
    {
        return (bool) (self::load()['dry_run'] ?? true);
    }
}
