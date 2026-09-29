<?php

namespace App\Services\Marketing;

/**
 * Troca um refresh_token (obtido uma vez, via consentimento OAuth2 de quem administra a
 * conta Gerenciadora do Google Ads) por um access_token de curta duração (~1h), a cada
 * chamada que precisar. Sem Composer/biblioteca oficial do Google — cURL direto, mesmo
 * padrão já usado no resto do FixaOS pra falar com API externa (WhatsAppService/
 * InfinitePayService).
 *
 * O refresh_token em si NUNCA passa por aqui em texto puro fora de memória — quem chama
 * decifra com CredentialCipher só no momento de montar este cliente (mesma regra já
 * documentada em CredentialCipher).
 */
class GoogleOAuthClient
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
    ) {
        if ($clientId === '' || $clientSecret === '') {
            throw new \RuntimeException('Client ID/Secret do Google OAuth ausente na configuração.');
        }
    }

    /** @return string access_token pronto pra usar no header Authorization: Bearer */
    public function refreshAccessToken(string $refreshToken): string
    {
        if ($refreshToken === '') {
            throw new \RuntimeException('refresh_token do Google Ads ausente — conta ainda não conectada.');
        }

        $ch = curl_init(self::TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POSTFIELDS     => http_build_query([
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type'    => 'refresh_token',
            ]),
        ]);
        $body = curl_exec($ch);
        $erroCurl = curl_errno($ch) !== 0 ? curl_error($ch) : null;
        curl_close($ch);

        if ($erroCurl !== null) {
            throw new \RuntimeException("Falha de rede ao renovar token do Google: {$erroCurl}");
        }

        $json = json_decode((string) $body, true);
        if (!is_array($json) || empty($json['access_token'])) {
            throw new \RuntimeException('Google OAuth não devolveu access_token: ' . self::descreverErro($json));
        }
        return (string) $json['access_token'];
    }

    /**
     * O corpo de erro do endpoint de token pode ecoar parâmetros da requisição em alguns
     * cenários de debug do Google — nunca propaga o corpo cru pra fora, só os campos
     * padronizados error/error_description (nenhum dos dois contém client_secret/refresh_token).
     */
    private static function descreverErro(mixed $json): string
    {
        if (!is_array($json) || !isset($json['error'])) return 'resposta inesperada do Google OAuth';
        $desc = (string) $json['error'];
        if (!empty($json['error_description'])) $desc .= ': ' . $json['error_description'];
        return $desc;
    }
}
