<?php

namespace App\Services\Marketing;

/**
 * Plataforma simulada usada pela "conta de demonstração", até a integração real da Meta
 * (Etapa 5) estar pronta e testada. Porta fiel de ads-platform/src/lib/ads/fake-platform.ts —
 * mesmos 4 perfis de campanha (pensados pra cada regra de alerta/sugestão ter algo pra achar)
 * e mesmo gerador determinístico (hash FNV-1a): a mesma conta, mesma campanha, mesmo dia
 * sempre gera o mesmo número, sem precisar gravar estado nenhum entre chamadas.
 *
 * Diferente do protótipo (Node, processo de vida longa, guarda escritas num Map em memória),
 * aqui cada requisição PHP é isolada — por isso `setCampaignStatus()`/`setDailyBudget()` não
 * persistem nada sozinhos nesta classe: a Etapa 3 (executor da fila) é quem grava a mudança
 * de verdade em `mkt_campaigns`, e o próximo `listCampaigns()` desta mesma classe volta a
 * gerar os valores-base do perfil — o "estado atual" de uma campanha fake vive no banco do
 * FixaOS, não dentro da plataforma simulada.
 */
class FakeAdPlatform implements AdPlatformInterface
{
    /** @var array<string, array{name:string,status:string,daily_budget_cents:int,spend_ratio:float,cpm_cents:int,ctr:float,lead_rate:float}> */
    private const PROFILES = [
        'tv'  => ['name' => 'Conserto de TV — Leads',            'status' => 'active', 'daily_budget_cents' => 4_000, 'spend_ratio' => 0.95, 'cpm_cents' => 1_800, 'ctr' => 0.014, 'lead_rate' => 0.09],
        'cel' => ['name' => 'Troca de tela de celular',          'status' => 'active', 'daily_budget_cents' => 3_500, 'spend_ratio' => 0.90, 'cpm_cents' => 2_200, 'ctr' => 0.018, 'lead_rate' => 0.04],
        'gel' => ['name' => 'Geladeira — atendimento em domicílio', 'status' => 'active', 'daily_budget_cents' => 2_500, 'spend_ratio' => 0.85, 'cpm_cents' => 1_500, 'ctr' => 0.009, 'lead_rate' => 0.00],
        'rmk' => ['name' => 'Remarketing — site',                'status' => 'paused', 'daily_budget_cents' => 1_500, 'spend_ratio' => 0.90, 'cpm_cents' => 3_000, 'ctr' => 0.020, 'lead_rate' => 0.10],
    ];

    public function id(): string
    {
        return 'fake';
    }

    public function listCampaigns(string $accountExternalId): array
    {
        $rows = [];
        foreach (self::PROFILES as $key => $profile) {
            $rows[] = [
                'external_id'        => $this->externalId($accountExternalId, $key),
                'name'               => $profile['name'],
                'status'             => $profile['status'],
                'daily_budget_cents' => $profile['daily_budget_cents'],
            ];
        }
        return $rows;
    }

    public function getDailyInsights(string $accountExternalId, string $from, string $to): array
    {
        $rows = [];
        foreach (Dates::eachDay($from, $to) as $date) {
            foreach (self::PROFILES as $key => $profile) {
                $rows[] = $this->insight($accountExternalId, $key, $profile, $date);
            }
        }
        return $rows;
    }

    public function setCampaignStatus(string $accountExternalId, string $campaignExternalId, string $status): void
    {
        if (!in_array($status, ['active', 'paused'], true)) {
            throw new \InvalidArgumentException("invalid status: {$status}");
        }
        $this->findProfileKey($accountExternalId, $campaignExternalId);
    }

    public function setDailyBudget(string $accountExternalId, string $campaignExternalId, int $dailyBudgetCents): void
    {
        if ($dailyBudgetCents <= 0) {
            throw new \InvalidArgumentException('invalid budget');
        }
        $this->findProfileKey($accountExternalId, $campaignExternalId);
    }

    private function findProfileKey(string $accountExternalId, string $campaignExternalId): string
    {
        foreach (array_keys(self::PROFILES) as $key) {
            if ($this->externalId($accountExternalId, $key) === $campaignExternalId) return $key;
        }
        throw new \RuntimeException("campaign not found: {$campaignExternalId}");
    }

    private function externalId(string $accountExternalId, string $key): string
    {
        return "{$accountExternalId}_{$key}";
    }

    /** @param array{name:string,status:string,daily_budget_cents:int,spend_ratio:float,cpm_cents:int,ctr:float,lead_rate:float} $profile */
    private function insight(string $accountExternalId, string $key, array $profile, string $date): array
    {
        $externalId = $this->externalId($accountExternalId, $key);
        $empty = ['campaign_external_id' => $externalId, 'date' => $date, 'spend_cents' => 0, 'impressions' => 0, 'clicks' => 0, 'leads' => 0];
        if ($profile['status'] !== 'active') return $empty;

        $seed = "{$accountExternalId}:{$key}:{$date}";
        $jitter = 0.8 + self::noise($seed) * 0.4;
        $spendCents = (int) round($profile['daily_budget_cents'] * $profile['spend_ratio'] * $jitter);
        $impressions = (int) round(($spendCents / $profile['cpm_cents']) * 1000);
        $clicks = (int) round($impressions * $profile['ctr'] * $jitter);
        $leads = (int) floor($clicks * $profile['lead_rate'] * (0.5 + self::noise("{$seed}:leads")));

        return [
            'campaign_external_id' => $externalId,
            'date'                 => $date,
            'spend_cents'          => $spendCents,
            'impressions'          => $impressions,
            'clicks'               => $clicks,
            'leads'                => $leads,
        ];
    }

    /** Valor determinístico em [0, 1) — mesma seed sempre gera o mesmo número. Hash FNV-1a. */
    public static function noise(string $seed): float
    {
        $hash = 2166136261;
        $len = strlen($seed);
        for ($i = 0; $i < $len; $i++) {
            $hash ^= ord($seed[$i]);
            $hash = self::imul32($hash, 16777619);
        }
        $unsigned = $hash & 0xFFFFFFFF;
        return $unsigned / 2 ** 32;
    }

    /** Multiplicação 32 bits com overflow (equivalente a Math.imul do JS). */
    private static function imul32(int $a, int $b): int
    {
        $result = ($a * $b) & 0xFFFFFFFF;
        return $result >= 0x80000000 ? $result - 0x100000000 : $result;
    }
}
