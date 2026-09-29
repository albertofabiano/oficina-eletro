<?php

namespace App\Services\Marketing;

use PDO;

/**
 * Resolve qual AdPlatformInterface usar pra UMA conta (`mkt_ad_accounts.platform`) — ponto
 * único de montagem, usado tanto pelo botão "Sincronizar agora" (MarketingController) quanto
 * pelo cron de coleta agendada (scripts/marketing_sincronizar.php), pra nunca ter duas lógicas
 * de resolução divergindo entre si.
 *
 * `google_ads` busca a credencial GLOBAL (scope='global' — modelo agência, uma conta
 * Gerenciadora só, compartilhada por toda empresa cliente) em mkt_credentials e decifra o
 * refresh_token com CredentialCipher; sem essa linha ainda gravada (nenhuma "tela de conectar"
 * existe até aqui), lança um erro claro em vez de tentar seguir com token vazio.
 */
class PlatformFactory
{
    public static function make(PDO $db, array $account, ?array $config = null): AdPlatformInterface
    {
        $config ??= self::loadConfig();
        return match ($account['platform']) {
            'fake'       => new FakeAdPlatform(),
            'google_ads' => self::googleAds($db, $config),
            'meta'       => throw new \RuntimeException('Integração com a Meta ainda não implementada (Google Ads veio primeiro, por decisão do dono do produto).'),
            default      => throw new \RuntimeException("Plataforma de anúncio desconhecida: \"{$account['platform']}\"."),
        };
    }

    private static function loadConfig(): array
    {
        $arquivo = BASE_PATH . '/config/marketing.php';
        return is_file($arquivo) ? require $arquivo : [];
    }

    private static function googleAds(PDO $db, array $config): GoogleAdsPlatform
    {
        $googleCfg = $config['google_ads'] ?? [];

        $stmt = $db->prepare(
            "SELECT token_ciphertext, token_nonce FROM mkt_credentials
             WHERE scope = 'global' AND platform = 'google_ads' ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute();
        $cred = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cred) {
            throw new \RuntimeException('Google Ads ainda não conectado — falta gerar o refresh_token da conta Gerenciadora e gravar em mkt_credentials.');
        }

        $refreshToken = (new CredentialCipher($config))->decrypt($cred['token_ciphertext'], $cred['token_nonce']);

        return new GoogleAdsPlatform(
            (string) ($googleCfg['developer_token'] ?? ''),
            (string) ($googleCfg['login_customer_id'] ?? ''),
            $refreshToken,
            new GoogleOAuthClient((string) ($googleCfg['client_id'] ?? ''), (string) ($googleCfg['client_secret'] ?? '')),
            (string) ($googleCfg['api_version'] ?? 'v18'),
        );
    }
}
