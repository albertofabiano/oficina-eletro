<?php

namespace App\Services\Marketing;

/**
 * Implementação real do AdPlatformInterface pro Google Ads — API REST (versão configurável via
 * $apiVersion/config/marketing.php, padrão v25 — o Google sunsetta versão a cada ~12 meses,
 * ver tools/debug_google_ads.php se a versão em uso começar a devolver 404 puro do servidor,
 * sinal de que a versão configurada já saiu do ar; não a lib oficial
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
        private readonly string $apiVersion = 'v25',
    ) {
        // developer_token: opcional desde a mudança do Google em 09/09/2026 (header ignorado
        // pela API, nível de acesso passou a ser do projeto do Google Cloud) — só continua
        // aceito por compatibilidade até a remoção definitiva prevista pro Google pra 2027,
        // então NÃO bloqueia mais aqui. login_customer_id continua obrigatório (sem ele não dá
        // pra autenticar como a conta Gerenciadora de jeito nenhum).
        if ($loginCustomerId === '') throw new \RuntimeException('login_customer_id (conta Gerenciadora) ausente na configuração.');
    }

    private ?string $accessTokenCache = null;

    public function id(): string
    {
        return 'google_ads';
    }

    /**
     * Confere se a conta Gerenciadora de fato enxerga este Customer ID (precisa já estar
     * vinculado no Google Ads, via convite aceito) e devolve os dados básicos pra gravar em
     * mkt_ad_accounts — usado só na hora de CONECTAR uma conta nova (MarketingController),
     * nunca durante a sincronização de rotina (que já confia no que já está gravado).
     * Lança RuntimeException (mensagem já sem token, via describeError) se a conta não
     * existir, não estiver vinculada, ou qualquer outro erro da API.
     */
    public function resolverConta(string $accountExternalId): array
    {
        $gaql = "SELECT customer.id, customer.descriptive_name, customer.currency_code FROM customer LIMIT 1";
        $rows = $this->search($accountExternalId, $gaql);
        $customer = $rows[0]['customer'] ?? null;
        if ($customer === null) {
            throw new \RuntimeException('Não foi possível ler os dados dessa conta no Google Ads — confirme o Customer ID e se o convite de vínculo com a conta Gerenciadora já foi aceito.');
        }
        return [
            'external_id' => (string) ($customer['id'] ?? $accountExternalId),
            'name'        => (string) ($customer['descriptiveName'] ?? ('Conta ' . $accountExternalId)),
            'currency'    => (string) ($customer['currencyCode'] ?? 'BRL'),
        ];
    }

    /**
     * Manda o convite de vínculo da conta Gerenciadora pro Customer ID informado —
     * equivalente à ação manual "Vincular conta" que um admin faria dentro do próprio Google
     * Ads, só que iniciada pelo FixaOS (a Gerenciadora já autorizada) em vez de pedir pro
     * cliente ir lá digitar o Customer ID da Gerenciadora sozinho. Fica PENDING no lado do
     * Google até o DONO da conta aceitar — isso nunca pode ser automatizado por aqui, é
     * consentimento explícito de quem tem a conta, não uma etapa técnica.
     * Chamada em customers/{GERENCIADORA}/... (não na conta do cliente) — mutate cria o link
     * do lado de quem convida; ver CustomerClientLinkService na documentação do Google Ads API.
     */
    public function enviarConviteVinculo(string $accountExternalId): void
    {
        // Diferente de campaigns:mutate/campaignBudgets:mutate (lote, campo "operations" no
        // plural) — CustomerClientLinkService só aceita 1 operação por chamada, campo
        // "operation" no singular. Confirmado batendo cabeça com a API de verdade: mandar
        // "operations" (plural) aqui devolve 400 "Unknown name \"operations\": Cannot find field."
        $this->call('POST', "customers/{$this->loginCustomerId}/customerClientLinks:mutate", [
            'operation' => [
                'create' => [
                    'clientCustomer' => "customers/{$accountExternalId}",
                    'status'         => 'PENDING',
                ],
            ],
        ], $this->loginCustomerId);
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

    /**
     * Anúncios de uma campanha (independe de quantos grupos de anúncio existem por trás —
     * o objetivo é o cliente ENXERGAR só a campanha, sem precisar aprender o conceito de
     * "grupo de anúncios" do Google Ads; o nome do grupo só vai junto como referência).
     * Só extrai texto de Anúncio de Pesquisa Responsivo (o tipo padrão hoje em dia) pro preview
     * — outros tipos (imagem, vídeo, Performance Max) caem no fallback "Anúncio #id (tipo)".
     */
    public function listAds(string $accountExternalId, string $campaignExternalId, string $from, string $to): array
    {
        $this->validarIdCampanha($campaignExternalId);
        if (!Dates::isIsoDate($from) || !Dates::isIsoDate($to)) {
            throw new \InvalidArgumentException('from/to precisam ser datas ISO (AAAA-MM-DD)');
        }
        $gaql = "SELECT ad_group_ad.resource_name, ad_group_ad.status, ad_group.id, ad_group.name,
                         ad_group_ad.ad.id, ad_group_ad.ad.type, ad_group_ad.ad.final_urls,
                         ad_group_ad.ad.responsive_search_ad.headlines, ad_group_ad.ad.responsive_search_ad.descriptions,
                         metrics.clicks, metrics.impressions, metrics.conversions, metrics.cost_micros
                  FROM ad_group_ad
                  WHERE campaign.id = {$campaignExternalId}
                    AND ad_group_ad.status != 'REMOVED'
                    AND segments.date BETWEEN '{$from}' AND '{$to}'";
        return self::parseAdsResponse($this->search($accountExternalId, $gaql));
    }

    /**
     * @return array<int, array{resource_name:string,ad_group_name:string,ad_id:string,ad_type:string,
     *   headlines:string[],descriptions:string[],final_url:?string,preview:string,status:string,
     *   clicks:int,impressions:int,leads:int,spend_cents:int}>
     * `headlines`/`descriptions` vêm TODAS as variações cadastradas (até 15 títulos/4
     * descrições num Anúncio de Pesquisa Responsivo) — o Google combina dinamicamente qual
     * aparece de fato pro usuário final, então mostrar o "pool" completo é mais honesto do que
     * fingir saber qual combinação específica foi exibida. `preview` continua existindo (só a
     * 1ª headline) pra qualquer lugar que só precise de um resumo curto de 1 linha.
     */
    public static function parseAdsResponse(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $adGroupAd = $row['adGroupAd'] ?? [];
            $ad = $adGroupAd['ad'] ?? [];
            $adGroup = $row['adGroup'] ?? [];
            $metrics = $row['metrics'] ?? [];
            $headlines = array_map(fn($h) => (string) ($h['text'] ?? ''), $ad['responsiveSearchAd']['headlines'] ?? []);
            $descriptions = array_map(fn($d) => (string) ($d['text'] ?? ''), $ad['responsiveSearchAd']['descriptions'] ?? []);
            $adId = (string) ($ad['id'] ?? '');
            $out[] = [
                'resource_name' => (string) ($adGroupAd['resourceName'] ?? ''),
                'ad_group_name' => (string) ($adGroup['name'] ?? ''),
                'ad_id'         => $adId,
                'ad_type'       => (string) ($ad['type'] ?? 'UNKNOWN'),
                'headlines'     => $headlines,
                'descriptions'  => $descriptions,
                'final_url'     => $ad['finalUrls'][0] ?? null,
                'preview'       => $headlines[0] ?? ('Anúncio #' . $adId),
                'status'        => self::mapStatus((string) ($adGroupAd['status'] ?? 'UNKNOWN')),
                'clicks'        => (int) ($metrics['clicks'] ?? 0),
                'impressions'   => (int) ($metrics['impressions'] ?? 0),
                'leads'         => (int) round((float) ($metrics['conversions'] ?? 0)),
                'spend_cents'   => self::microsToCents((int) ($metrics['costMicros'] ?? 0)),
            ];
        }
        return $out;
    }

    public function setAdStatus(string $accountExternalId, string $adResourceName, string $status): void
    {
        if (!in_array($status, ['active', 'paused'], true)) {
            throw new \InvalidArgumentException("invalid status: {$status}");
        }
        // AdGroupAdService.MutateAdGroupAds (plural, ver comentário de enviarConviteVinculo()
        // sobre a mesma diferença) — campo "operations" no plural, mesmo formato de
        // setCampaignStatus()/setDailyBudget() acima.
        $googleStatus = $status === 'active' ? 'ENABLED' : 'PAUSED';
        $this->call('POST', "customers/{$accountExternalId}/adGroupAds:mutate", [
            'operations' => [[
                'updateMask' => 'status',
                'update'     => ['resourceName' => $adResourceName, 'status' => $googleStatus],
            ]],
        ], $accountExternalId);
    }

    // ── Palavras-chave (Etapa 3) ─────────────────────────────────────────────────────────────
    // Termos que fazem a campanha aparecer (palavra-chave) ou NUNCA aparecer (negativa) numa
    // busca. Palavra-chave positiva só existe presa a um "grupo de anúncios" — o mesmo conceito
    // que o resto do painel esconde de propósito (ver comentário de listAds()); pra não obrigar
    // o cliente a entender isso, addKeywords() recebe o grupo já resolvido pelo controller (que
    // auto-escolhe quando a campanha só tem 1 grupo, e só pede escolha quando há mais de um).
    // Palavra-chave NEGATIVA entra direto na CAMPANHA (CampaignCriterionService), não no grupo —
    // bloqueia a campanha inteira de aparecer pra aquele termo, sem precisar escolher grupo
    // nenhum; é o nível mais simples que a API do Google oferece pra negativa.

    private const MATCH_TYPES = ['BROAD', 'PHRASE', 'EXACT'];

    public static function validarMatchType(string $matchType): string
    {
        $m = strtoupper(trim($matchType));
        if (!in_array($m, self::MATCH_TYPES, true)) {
            throw new \InvalidArgumentException("invalid match type: {$matchType}");
        }
        return $m;
    }

    /**
     * Recebe o texto bruto de uma <textarea> (1 palavra-chave por linha), tira linha vazia,
     * espaço nas pontas e duplicata (case-insensitive) e limita a 50 por envio — teto generoso
     * pra uso manual, mas que evita um mutate gigante por acidente (ex.: colar uma lista de
     * milhares de linhas vinda de outro lugar).
     * @return string[]
     */
    public static function normalizarListaPalavras(string $bruto): array
    {
        $linhas = preg_split('/\r\n|\r|\n/', $bruto) ?: [];
        $vistos = [];
        $out = [];
        foreach ($linhas as $linha) {
            $t = trim($linha);
            if ($t === '') continue;
            $chave = mb_strtolower($t);
            if (isset($vistos[$chave])) continue;
            $vistos[$chave] = true;
            $out[] = $t;
        }
        return array_slice($out, 0, 50);
    }

    /**
     * Grupos de anúncio de uma campanha — usado só pra resolver ONDE uma palavra-chave positiva
     * nova deve entrar (ver comentário da seção acima), nunca exibido como uma tela própria.
     * @return array<int, array{resource_name:string,id:string,name:string}>
     */
    public function listAdGroups(string $accountExternalId, string $campaignExternalId): array
    {
        $this->validarIdCampanha($campaignExternalId);
        $gaql = "SELECT ad_group.resource_name, ad_group.id, ad_group.name
                  FROM ad_group
                  WHERE campaign.id = {$campaignExternalId}
                    AND ad_group.status != 'REMOVED'";
        $rows = $this->search($accountExternalId, $gaql);
        $out = [];
        foreach ($rows as $row) {
            $ag = $row['adGroup'] ?? [];
            $out[] = [
                'resource_name' => (string) ($ag['resourceName'] ?? ''),
                'id'            => (string) ($ag['id'] ?? ''),
                'name'          => (string) ($ag['name'] ?? ''),
            ];
        }
        return $out;
    }

    /**
     * Palavras-chave (positivas) de uma campanha, com métricas do período — usa o recurso
     * `keyword_view`, que só devolve critério do tipo KEYWORD não-negativo com estatística (é
     * por isso que a negativa, abaixo, precisa de uma consulta separada em outro recurso).
     */
    public function listKeywords(string $accountExternalId, string $campaignExternalId, string $from, string $to): array
    {
        $this->validarIdCampanha($campaignExternalId);
        if (!Dates::isIsoDate($from) || !Dates::isIsoDate($to)) {
            throw new \InvalidArgumentException('from/to precisam ser datas ISO (AAAA-MM-DD)');
        }
        $gaql = "SELECT ad_group_criterion.resource_name, ad_group_criterion.status,
                         ad_group_criterion.keyword.text, ad_group_criterion.keyword.match_type,
                         ad_group.id, ad_group.name,
                         metrics.clicks, metrics.impressions, metrics.conversions, metrics.cost_micros
                  FROM keyword_view
                  WHERE campaign.id = {$campaignExternalId}
                    AND segments.date BETWEEN '{$from}' AND '{$to}'";
        return self::parseKeywordsResponse($this->search($accountExternalId, $gaql));
    }

    /**
     * @return array<int, array{resource_name:string,ad_group_name:string,text:string,
     *   match_type:string,status:string,clicks:int,impressions:int,leads:int,spend_cents:int}>
     */
    public static function parseKeywordsResponse(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $crit = $row['adGroupCriterion'] ?? [];
            $adGroup = $row['adGroup'] ?? [];
            $metrics = $row['metrics'] ?? [];
            $out[] = [
                'resource_name' => (string) ($crit['resourceName'] ?? ''),
                'ad_group_name' => (string) ($adGroup['name'] ?? ''),
                'text'          => (string) ($crit['keyword']['text'] ?? ''),
                'match_type'    => (string) ($crit['keyword']['matchType'] ?? 'UNKNOWN'),
                'status'        => self::mapStatus((string) ($crit['status'] ?? 'UNKNOWN')),
                'clicks'        => (int) ($metrics['clicks'] ?? 0),
                'impressions'   => (int) ($metrics['impressions'] ?? 0),
                'leads'         => (int) round((float) ($metrics['conversions'] ?? 0)),
                'spend_cents'   => self::microsToCents((int) ($metrics['costMicros'] ?? 0)),
            ];
        }
        return $out;
    }

    /**
     * Palavras-chave NEGATIVAS de uma campanha — vivem em `campaign_criterion` (não em
     * `ad_group_criterion`/`keyword_view`, que só devolve critério POSITIVO com estatística),
     * bloqueando a campanha inteira de aparecer pra aquele termo. Sem métrica: um termo negativo
     * não "gasta" nem "clica" por definição — ele existe pra IMPEDIR que a campanha apareça.
     */
    public function listNegativeKeywords(string $accountExternalId, string $campaignExternalId): array
    {
        $this->validarIdCampanha($campaignExternalId);
        $gaql = "SELECT campaign_criterion.resource_name, campaign_criterion.keyword.text,
                         campaign_criterion.keyword.match_type
                  FROM campaign_criterion
                  WHERE campaign.id = {$campaignExternalId}
                    AND campaign_criterion.type = 'KEYWORD'
                    AND campaign_criterion.negative = TRUE";
        return self::parseNegativeKeywordsResponse($this->search($accountExternalId, $gaql));
    }

    /** @return array<int, array{resource_name:string,text:string,match_type:string}> */
    public static function parseNegativeKeywordsResponse(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $crit = $row['campaignCriterion'] ?? [];
            $out[] = [
                'resource_name' => (string) ($crit['resourceName'] ?? ''),
                'text'          => (string) ($crit['keyword']['text'] ?? ''),
                'match_type'    => (string) ($crit['keyword']['matchType'] ?? 'UNKNOWN'),
            ];
        }
        return $out;
    }

    /** Cria uma ou mais palavras-chave no grupo de anúncios informado. Retorna quantas foram criadas. */
    public function addKeywords(string $accountExternalId, string $adGroupResourceName, array $keywords, string $matchType): int
    {
        $matchType = self::validarMatchType($matchType);
        $ops = [];
        foreach ($keywords as $texto) {
            $ops[] = [
                'create' => [
                    'adGroup' => $adGroupResourceName,
                    'status'  => 'ENABLED',
                    'keyword' => ['text' => $texto, 'matchType' => $matchType],
                ],
            ];
        }
        if (!$ops) return 0;
        // AdGroupCriterionService.MutateAdGroupCriteria (plural, mesmo padrão de
        // setCampaignStatus()/setAdStatus()) — campo "operations".
        $this->call('POST', "customers/{$accountExternalId}/adGroupCriteria:mutate", ['operations' => $ops], $accountExternalId);
        return count($ops);
    }

    /** Cria uma ou mais palavras-chave negativas na CAMPANHA (não no grupo). Retorna quantas foram criadas. */
    public function addNegativeKeywords(string $accountExternalId, string $campaignExternalId, array $keywords, string $matchType): int
    {
        $this->validarIdCampanha($campaignExternalId);
        $matchType = self::validarMatchType($matchType);
        $ops = [];
        foreach ($keywords as $texto) {
            $ops[] = [
                'create' => [
                    'campaign' => "customers/{$accountExternalId}/campaigns/{$campaignExternalId}",
                    'negative' => true,
                    'keyword'  => ['text' => $texto, 'matchType' => $matchType],
                ],
            ];
        }
        if (!$ops) return 0;
        // CampaignCriterionService.MutateCampaignCriteria (plural) — campo "operations".
        $this->call('POST', "customers/{$accountExternalId}/campaignCriteria:mutate", ['operations' => $ops], $accountExternalId);
        return count($ops);
    }

    public function removeKeyword(string $accountExternalId, string $criterionResourceName): void
    {
        $this->call('POST', "customers/{$accountExternalId}/adGroupCriteria:mutate", [
            'operations' => [['remove' => $criterionResourceName]],
        ], $accountExternalId);
    }

    public function removeNegativeKeyword(string $accountExternalId, string $criterionResourceName): void
    {
        $this->call('POST', "customers/{$accountExternalId}/campaignCriteria:mutate", [
            'operations' => [['remove' => $criterionResourceName]],
        ], $accountExternalId);
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

    /**
     * Traduções pra português dos códigos de erro mais comuns que essa integração encontra na
     * prática (permissão/vínculo, principalmente) — o Google não tem essa tradução pronta, a
     * API sempre responde em inglês. Cobre só os casos já observados de verdade; qualquer
     * código fora dessa lista cai no texto original do Google (em inglês), nunca escondido.
     */
    private const TRADUCOES_ERRO = [
        'USER_PERMISSION_DENIED' => 'Essa conta ainda não está vinculada de verdade à Gerenciadora — confirme que o convite foi aceito no Google Ads (pode levar alguns minutos pra valer depois de aceitar; se já esperou, confira em "Acesso e segurança → Administradores" se o vínculo aparece ativo).',
        'CUSTOMER_NOT_FOUND'      => 'Esse Customer ID não existe no Google Ads — confira se digitou certo.',
        'INVALID_CUSTOMER_ID'     => 'Customer ID inválido — confira se digitou certo (10 dígitos).',
        'NOT_ADS_USER'            => 'Essa conta não é reconhecida como uma conta de anúncios pelo Google Ads.',
    ];

    /** Extrai uma mensagem segura (sem token nenhum) do formato de erro padrão da API do Google. */
    public static function describeError(array $json): string
    {
        $msg = $json['error']['message'] ?? null;
        if ($msg === null) return 'Erro desconhecido na API do Google Ads.';
        $detalhes = [];
        $traduzido = null;
        foreach ($json['error']['details'] ?? [] as $detail) {
            foreach ($detail['errors'] ?? [] as $e) {
                if (!empty($e['message'])) $detalhes[] = $e['message'];
                $codigo = self::extrairCodigoErro($e['errorCode'] ?? null);
                if ($traduzido === null && $codigo !== null && isset(self::TRADUCOES_ERRO[$codigo])) {
                    $traduzido = self::TRADUCOES_ERRO[$codigo];
                }
            }
        }
        if ($traduzido !== null) return $traduzido;

        $completo = $msg . ($detalhes ? ' — ' . implode('; ', $detalhes) : '');
        return substr($completo, 0, 500); // mesmo limite de mkt_ad_accounts.last_sync_error
    }

    /**
     * `errorCode` do Google Ads vem como {"categoria": "VALOR"} — a categoria varia
     * (authorizationError/requestError/fieldError/...), só o VALOR importa pra bater com
     * TRADUCOES_ERRO. Testável isolado, sem precisar montar o JSON inteiro do erro.
     */
    public static function extrairCodigoErro(?array $errorCode): ?string
    {
        if (!$errorCode) return null;
        $valor = reset($errorCode);
        return $valor === false ? null : (string) $valor;
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
            'Content-Type: application/json',
        ];
        // Opcional (ver comentário do construtor) — só manda o header se de fato houver um
        // valor configurado, em vez de sempre mandar (mesmo vazio) sem necessidade.
        if ($this->developerToken !== '') {
            $headers[] = 'developer-token: ' . $this->developerToken;
        }
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
