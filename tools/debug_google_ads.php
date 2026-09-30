<?php
/**
 * Diagnóstico: faz uma chamada real ao Google Ads pra um Customer ID específico e imprime o
 * status HTTP + corpo cru da resposta — nunca imprime token nenhum (nem access nem refresh).
 * Existe porque as mensagens de erro resumidas do sistema (describeError()) às vezes não dão
 * detalhe suficiente pra diagnosticar sem ver a resposta de verdade.
 *
 * Rodar no VPS (nunca no sandbox — precisa da credencial real gravada em mkt_credentials):
 *   php tools/debug_google_ads.php <customer_id>              # testa resolverConta() (leitura)
 *   php tools/debug_google_ads.php <customer_id> convite      # testa enviarConviteVinculo()
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Core/DB.php';
require BASE_PATH . '/app/Services/Marketing/CredentialCipher.php';
require BASE_PATH . '/app/Services/Marketing/GoogleOAuthClient.php';

use App\Core\DB;
use App\Services\Marketing\CredentialCipher;
use App\Services\Marketing\GoogleOAuthClient;

$customerId = preg_replace('/\D+/', '', $argv[1] ?? '');
$modo = $argv[2] ?? 'leitura';
if (strlen($customerId) !== 10) {
    fwrite(STDERR, "Uso: php tools/debug_google_ads.php <customer_id de 10 dígitos> [convite]\n");
    exit(1);
}

$config = require BASE_PATH . '/config/marketing.php';
$googleCfg = $config['google_ads'] ?? [];

$db = DB::pdo();
$stmt = $db->query(
    "SELECT token_ciphertext, token_nonce FROM mkt_credentials
     WHERE scope='global' AND platform='google_ads' ORDER BY id DESC LIMIT 1"
);
$cred = $stmt->fetch();
if (!$cred) {
    fwrite(STDERR, "Nenhuma credencial gravada em mkt_credentials ainda.\n");
    exit(1);
}

$cipher = new CredentialCipher($config);
$refreshToken = $cipher->decrypt($cred['token_ciphertext'], $cred['token_nonce']);

$oauth = new GoogleOAuthClient((string) $googleCfg['client_id'], (string) $googleCfg['client_secret']);
$accessToken = $oauth->refreshAccessToken($refreshToken);
echo "Access token obtido OK (", strlen($accessToken), " caracteres, não impresso).\n\n";

$loginCustomerId = (string) $googleCfg['login_customer_id'];
$apiVersion = (string) ($googleCfg['api_version'] ?? 'v25');
$developerToken = (string) ($googleCfg['developer_token'] ?? '');

$headers = [
    'Authorization: Bearer ' . $accessToken,
    'Content-Type: application/json',
    'login-customer-id: ' . $loginCustomerId,
];
if ($developerToken !== '') {
    $headers[] = 'developer-token: ' . $developerToken;
}

if ($modo === 'convite') {
    $url = "https://googleads.googleapis.com/{$apiVersion}/customers/{$loginCustomerId}/customerClientLinks:mutate";
    $body = [
        'operations' => [[
            'create' => [
                'clientCustomer' => "customers/{$customerId}",
                'status'         => 'PENDING',
            ],
        ]],
    ];
    echo "Modo: enviar convite de vínculo (customerClientLinks:mutate)\n";
} else {
    $url = "https://googleads.googleapis.com/{$apiVersion}/customers/{$customerId}/googleAds:search";
    $body = ['query' => "SELECT customer.id, customer.descriptive_name, customer.currency_code FROM customer LIMIT 1"];
    echo "Modo: ler dados da conta (googleAds:search)\n";
}

echo "POST: {$url}\n";
echo "login-customer-id: {$loginCustomerId}\n";
echo "corpo enviado: ", json_encode($body, JSON_UNESCAPED_SLASHES), "\n\n";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_CUSTOMREQUEST  => 'POST',
    CURLOPT_POSTFIELDS     => json_encode($body),
]);
$raw = curl_exec($ch);
$erroCurl = curl_errno($ch) !== 0 ? curl_error($ch) : null;
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($erroCurl !== null) {
    echo "Erro de rede (cURL): {$erroCurl}\n";
    exit(1);
}

echo "HTTP status: {$status}\n";
echo "Corpo da resposta:\n";
$json = json_decode((string) $raw, true);
echo $json !== null ? json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $raw;
echo "\n";
