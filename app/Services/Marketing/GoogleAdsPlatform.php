<?php

namespace App\Services\Marketing;

/**
 * Implementação real do AdPlatformInterface pro Google Ads — API REST v18 (não a lib oficial
 * do Google, que exige Composer; chamada direta via cURL, mesmo padrão do resto do FixaOS).
 * Modelo agência: uma conta Gerenciadora (MCC) do Google Ads enxerga a conta de anúncio de
 * cada empresa cliente; autentica sempre como o usuário dono da MCC (`login-customer-id`),
 * nunca pede OAuth de cada empresa individualmente — equivalente ao token de usuário de
 * sistema global já usado (ou planejado) pra Meta.
 *
 * `external_id` de conta = Customer ID do Google Ads só com dígitos (sem os traços que a
 * interface do Google mostra, ex. "123-456-7890" -> "1234567890"). `external_id` de campanha
 * = o `campaign.id` numérico do Google Ads (string), sem prefixo — já é único dentro da conta.
 *
 * Duas conversões que valem sempre nesta classe:
 *  - "lead" pro FixaOS = `metrics.conversions` do Google Ads (arredondado pra inteiro) — a
 *    conta precisa só rastrear conversão do tipo lead pra esse número fazer sentido; refinar
 *    por ação de conversão específica fica pra depois, se algum piloto precisar.
 *  - dinheiro sempre em "micros" na API do Google (1.000.000 micros = 1 unidade monetária =
 *    100 centavos) -> `microsToCents()`/centavos->micros fazem essa conversão sem passar por
 *    float, mesma regra inegociável nº4 já usada em Money.
 */
class GoogleAdsPlatform implements AdPlatformInterface
{
    public function __construct(
        private readonly string $developerToken,
        private readonly string $loginCustomerId,
        private readonly string $refreshToken,
        private readonly GoogleOAuthClient $oauth,
        private readonly string $apiVersion = 'v18',
    ) {
        if ($developerToken === '') throw new \RuntimeException('Developer Token do Google Ads ausente na configuração.');
        if ($loginCustomerId === '') throw new \RuntimeException('login_customer_id (conta Gerenciadora) ausente na configuração.');
    }

    private ?string $accessTokenCache = null;

    public function id(): string
    {
        return 'google_ads';
    }

    public function listCampaigns(string $accountExternalId): array
    {
        $gaql = "SELECT campaign.id, campaign.name, campaign.status, campaign_budget.amount_micros
                  FROM campaign
                  WHERE campaign.status != 'REMOVED'";
        return self::parseCampaignsResponse($this->search($accountExternalId, $gaql));
    }

    public function getDailyInsights(string $accountExternalId, string $from, string $to): array
    {
        if (!Dates::isIsoDate($from) || !Dates::isIsoDate($to)) {
            // $from/$to sempre vêm de Dates:: internamente, nunca de input direto do usuário —
            // mas como entram numa string GAQL por interpolação, validar antes é mais barato
            // que confiar nisso pra sempre.
            throw new \InvalidArgumentException('from/to precisam ser datas ISO (AAAA-MM-DD)');
        }
        $gaql = "SELECT campaign.id, segments.date, metrics.cost_micros, metrics.impressions,
                         metrics.clicks, metrics.conversions
                  FROM campaign
                  WHERE segments.date BETWEEN '{$from}' AND '{$to}'";
        return self::parseInsightsResponse($this->search($accountExternalId, $gaql));
    }

    public function setCampaignStatus(string $accountExternalId, string $campaignExternalId, string $status): void
    {
        if (!in_array($status, ['active', 'paused'], true)) {
            throw new \InvalidArgumentException("invalid status: {$status}");
        }
        $this->validarIdCampanha($campaignExternalId);
        $googleStatus = $status === 'active' ? 'ENABLED' : 'PAUSED';
        $resourceName = "customers/{$accountExternalId}/campaigns/{$campaignExternalId}";

        $this->call('POST', "customers/{$accountExternalId}/campaigns:mutate", [
            'operations' => [[
                // updateMask usa o nome de campo do proto (snake_case) — diferente do corpo
                // JSON em si (camelCase, "status"/"amountMicros" etc.), é fácil confundir os dois.
                'updateMask' => 'status',
                'update'     => ['resourceName' => $resourceName, 'status' => $googleStatus],
            ]],
        ], $accountExternalId);
    }

    public function setDailyBudget(string $accountExternalId, string $campaignExternalId, int $dailyBudgetCents): void
    {
        if ($dailyBudgetCents <= 0) {
            throw new \InvalidArgumentException('invalid budget');
        }
        $this->validarIdCampanha($campaignExternalId);

        // Orçamento vive num recurso PRÓPRIO (campaign_budget), não na campanha — precisa
        // resolver o resource_name dele antes de poder alterar o valor.
        $gaql = "SELECT campaign_budget.resource_name FROM campaign WHERE campaign.id = {$campaignExternalId}";
        $rows = $this->search($accountExternalId, $gaql);
        $budgetResourceName = $rows[0]['campaignBudget']['resourceName'] ?? null;
        if ($budgetResourceName === null) {
            throw new \RuntimeException("campaign not found: {$campaignExternalId}");
        }

        $this->call('POST', "customers/{$accountExternalId}/campaignBudgets:mutate", [
            'operations' => [[
                'updateMask' => 'amount_micros',
                'update'     => ['resourceName' => $budgetResourceName, 'amountMicros' => (string) self::centsToMicros($dailyBudgetCents)],
            ]],
        ], $accountExternalId);
    }

    private function validarIdCampanha(string $campaignExternalId): void
    {
        if (!ctype_digit($campaignExternalId)) {
            throw new \InvalidArgumentException("invalid campaign id: {$campaignExternalId}");
        }
    }

    // ── Conversões de dinheiro (sempre inteiro, nunca float) ────────────────────────────────

    public static function microsToCents(int $micros): int
    {
        return (int) round($micros / 10_000);
    }

    public static function centsToMicros(int $cents): int
    {
        return $cents * 10_000;
    }

    // ── Parsing puro (testável sem rede) ─────────────────────────────────────────────────────

    /** @return array<int, array{external_id:string,name:string,status:string,daily_budget_cents:?int}> */
    public static function parseCampaignsResponse(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $c = $row['campaign'] ?? [];
            $budget = $row['campaignBudget'] ?? null;
            $out[] = [
                'external_id'        => (string) ($c['id'] ?? ''),
                'name'               => (string) ($c['name'] ?? ''),
                'status'             => self::mapStatus((string) ($c['status'] ?? 'UNKNOWN')),
                'daily_budget_cents' => ($budget !== null && isset($budget['amountMicros'])) ? self::microsToCents((int) $budget['amountMicros']) : null,
            ];
        }
        return $out;
    }

    /** @return array<int, array{campaign_external_id:string,date:string,spend_cents:int,impressions:int,clicks:int,leads:int}> */
    public static function parseInsightsResponse(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $campaign = $row['campaign'] ?? [];
            $segments = $row['segments'] ?? [];
            $metrics = $row['metrics'] ?? [];
            $out[] = [
                'campaign_external_id' => (string) ($campaign['id'] ?? ''),
                'date'                 => (string) ($segments['date'] ?? ''),
                'spend_cents'          => self::microsToCents((int) ($metrics['costMicros'] ?? 0)),
                'impressions'          => (int) ($metrics['impressions'] ?? 0),
                'clicks'               => (int) ($metrics['clicks'] ?? 0),
                'leads'                => (int) round((float) ($metrics['conversions'] ?? 0)),
            ];
        }
        return $out;
    }

    /** status desconhecido nunca deve contar como "ativa gastando" por engano — cai em pausada. */
    public static function mapStatus(string $googleStatus): string
    {
        return match ($googleStatus) {
            'ENABLED' => 'active',
            'PAUSED'  => 'paused',
            'REMOVED' => 'archived',
            default   => 'paused',
        };
    }

    /** Extrai uma mensagem segura (sem token nenhum) do formato de erro padrão da API do Google. */
    public static function describeError(array $json): string
    {
        $msg = $json['error']['message'] ?? null;
        if ($msg === null) return 'Erro desconhecido na API do Google Ads.';
        $detalhes = [];
        foreach ($json['error']['details'] ?? [] as $detail) {
            foreach ($detail['errors'] ?? [] as $e) {
                if (!empty($e['message'])) $detalhes[] = $e['message'];
            }
        }
        $completo = $msg . ($detalhes ? ' — ' . implode('; ', $detalhes) : '');
        return substr($completo, 0, 500); // mesmo limite de mkt_ad_accounts.last_sync_error
    }

    // ── Transporte HTTP ──────────────────────────────────────────────────────────────────────

    private function accessToken(): string
    {
        if ($this->accessTokenCache === null) {
            $this->accessTokenCache = $this->oauth->refreshAccessToken($this->refreshToken);
        }
        return $this->accessTokenCache;
    }

    /** Busca todas as páginas de uma consulta GAQL (googleAds:search pagina via pageToken). */
    private function search(string $customerId, string $gaql): array
    {
        $results = [];
        $pageToken = null;
        do {
            $body = ['query' => $gaql];
            if ($pageToken !== null) $body['pageToken'] = $pageToken;
            $resp = $this->call('POST', "customers/{$customerId}/googleAds:search", $body, $customerId);
            foreach ($resp['results'] ?? [] as $row) $results[] = $row;
            $pageToken = $resp['nextPageToken'] ?? null;
        } while ($pageToken !== null);
        return $results;
    }

    private function call(string $method, string $path, ?array $body, string $customerId): array
    {
        $headers = [
            'Authorization: Bearer ' . $this->accessToken(),
            'developer-token: ' . $this->developerToken,
            'Content-Type: application/json',
        ];
        // Sempre autentica como a Gerenciadora, mesmo consultando a conta de um cliente —
        // é assim que o "modelo agência" do Google Ads funciona (ver comentário da classe).
        $headers[] = 'login-customer-id: ' . $this->loginCustomerId;

        $ch = curl_init("https://googleads.googleapis.com/{$this->apiVersion}/{$path}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => $method,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }
        $raw = curl_exec($ch);
        $erroCurl = curl_errno($ch) !== 0 ? curl_error($ch) : null;
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($erroCurl !== null) {
            throw new \RuntimeException("Falha de rede ao chamar o Google Ads: {$erroCurl}");
        }
        $json = json_decode((string) $raw, true);
        if ($status >= 400) {
            throw new \RuntimeException(self::describeError(is_array($json) ? $json : []));
        }
        return is_array($json) ? $json : [];
    }
}
