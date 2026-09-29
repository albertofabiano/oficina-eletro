<?php

namespace App\Services\Marketing;

/**
 * Contrato comum pra Meta Ads, Google Ads e a plataforma simulada (conta de demonstração).
 * Porta fiel de ads-platform/src/lib/ads/platform.ts.
 *
 * Métodos de LEITURA são usados pelo coletor (SyncService). Métodos de ESCRITA só podem
 * ser chamados pelo EXECUTOR da fila de aprovação (Etapa 3), depois de uma aprovação
 * humana registrada no banco e fora de DRY_RUN — nunca direto de tela nem de regra de
 * sugestão. Ver regra inegociável nº1 do documento de especificação.
 *
 * Cada campanha/inserção retornada usa o formato:
 *   campanha: ['external_id' => string, 'name' => string, 'status' => 'active'|'paused'|'archived', 'daily_budget_cents' => int|null]
 *   inserção: ['campaign_external_id' => string, 'date' => 'AAAA-MM-DD', 'spend_cents' => int, 'impressions' => int, 'clicks' => int, 'leads' => int]
 */
interface AdPlatformInterface
{
    public function id(): string;

    /** @return array<int, array{external_id:string,name:string,status:string,daily_budget_cents:?int}> */
    public function listCampaigns(string $accountExternalId): array;

    /** @return array<int, array{campaign_external_id:string,date:string,spend_cents:int,impressions:int,clicks:int,leads:int}> */
    public function getDailyInsights(string $accountExternalId, string $from, string $to): array;

    public function setCampaignStatus(string $accountExternalId, string $campaignExternalId, string $status): void;

    public function setDailyBudget(string $accountExternalId, string $campaignExternalId, int $dailyBudgetCents): void;
}
