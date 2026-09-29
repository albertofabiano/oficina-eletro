<?php

namespace App\Services\Marketing;

/**
 * Métricas, período e alertas do painel — porta fiel de ads-platform/src/lib/dashboard/
 * (metrics.ts, period.ts, alerts.ts, load-dashboard.ts). Funções puras: recebem campanha e
 * inserção diária já carregadas (arrays associativos), sem tocar em banco — quem busca os
 * dados é MarketingController/SyncService.
 *
 * Formato de uma campanha:  ['id'=>int, 'external_id'=>string, 'name'=>string, 'status'=>string, 'daily_budget_cents'=>?int]
 * Formato de uma inserção:  ['campaign_id'=>int, 'date'=>'AAAA-MM-DD', 'spend_cents'=>int, 'impressions'=>int, 'clicks'=>int, 'leads'=>int]
 */
class Dashboard
{
    public const PERIOD_OPTIONS = [7, 14, 30];

    public static function parsePeriod(mixed $raw, int $fallback = 7): int
    {
        $value = is_array($raw) ? ($raw[0] ?? null) : $raw;
        $value = is_numeric($value) ? (int) $value : null;
        return in_array($value, self::PERIOD_OPTIONS, true) ? $value : $fallback;
    }

    /** Período terminando ONTEM (hoje ainda está incompleto) + o período anterior de mesmo tamanho. */
    public static function periodRanges(int $days, string $today): array
    {
        $to = Dates::addDays($today, -1);
        $from = Dates::addDays($to, -($days - 1));
        return [
            'current'  => ['from' => $from, 'to' => $to],
            'previous' => ['from' => Dates::addDays($from, -$days), 'to' => Dates::addDays($from, -1)],
        ];
    }

    /** @param array<int, array{spend_cents:int,impressions:int,clicks:int,leads:int}> $insights */
    public static function sumInsights(array $insights): array
    {
        $totals = ['spend_cents' => 0, 'impressions' => 0, 'clicks' => 0, 'leads' => 0];
        foreach ($insights as $row) {
            $totals['spend_cents'] += $row['spend_cents'];
            $totals['impressions'] += $row['impressions'];
            $totals['clicks'] += $row['clicks'];
            $totals['leads'] += $row['leads'];
        }
        return $totals;
    }

    /** @param array{spend_cents:int,impressions:int,clicks:int,leads:int} $totals */
    public static function deriveMetrics(array $totals): array
    {
        return $totals + [
            'cost_per_lead_cents'  => Money::divideCents($totals['spend_cents'], $totals['leads']),
            'cost_per_click_cents' => Money::divideCents($totals['spend_cents'], $totals['clicks']),
            'cpm_cents'            => Money::divideCents($totals['spend_cents'] * 1000, $totals['impressions']),
            'ctr'                  => $totals['impressions'] === 0 ? null : $totals['clicks'] / $totals['impressions'],
        ];
    }

    /** Variação relativa; null sem base de comparação (evita divisão por zero). */
    public static function percentChange(?float $current, ?float $previous): ?float
    {
        if ($current === null || $previous === null || $previous == 0.0) return null;
        return ($current - $previous) / $previous;
    }

    /** @param array<int, array{date:string}> $insights */
    public static function inRange(array $insights, array $range): array
    {
        return array_values(array_filter(
            $insights,
            fn($row) => $row['date'] >= $range['from'] && $row['date'] <= $range['to']
        ));
    }

    /** Um ponto por dia no período, com zero nos dias sem dado. */
    public static function dailySeries(array $insights, array $range): array
    {
        $byDay = [];
        foreach (Dates::eachDay($range['from'], $range['to']) as $date) {
            $byDay[$date] = ['date' => $date, 'spend_cents' => 0, 'leads' => 0];
        }
        foreach ($insights as $row) {
            if (!isset($byDay[$row['date']])) continue;
            $byDay[$row['date']]['spend_cents'] += $row['spend_cents'];
            $byDay[$row['date']]['leads'] += $row['leads'];
        }
        return array_values($byDay);
    }

    /** Campanhas com suas métricas, maior investimento primeiro. */
    public static function campaignRows(array $campaigns, array $insights): array
    {
        $grouped = [];
        foreach ($insights as $row) {
            $grouped[$row['campaign_id']][] = $row;
        }
        $rows = array_map(function ($campaign) use ($grouped) {
            return [
                'campaign' => $campaign,
                'metrics'  => self::deriveMetrics(self::sumInsights($grouped[$campaign['id']] ?? [])),
            ];
        }, $campaigns);
        usort($rows, fn($a, $b) => $b['metrics']['spend_cents'] <=> $a['metrics']['spend_cents']);
        return $rows;
    }

    /** Alertas só de leitura — nunca agem na campanha, qualquer mudança passa pela fila de aprovação. */
    public static function evaluateAlerts(array $rows, ?int $accountCostPerLeadCents, int $noLeadsMinSpendCents = 5_000, float $highCplFactor = 1.5): array
    {
        $alerts = [];
        foreach ($rows as $row) {
            $campaign = $row['campaign'];
            $metrics = $row['metrics'];
            if ($campaign['status'] !== 'active') continue;

            if ($metrics['leads'] === 0 && $metrics['spend_cents'] >= $noLeadsMinSpendCents) {
                $alerts[] = [
                    'id' => "no-leads:{$campaign['id']}", 'campaign_id' => $campaign['id'],
                    'severity' => 'critical', 'title' => 'Gastando sem gerar leads', 'description' => $campaign['name'],
                ];
                continue;
            }

            if (
                $accountCostPerLeadCents !== null && $metrics['cost_per_lead_cents'] !== null
                && $metrics['cost_per_lead_cents'] > $accountCostPerLeadCents * $highCplFactor
            ) {
                $alerts[] = [
                    'id' => "high-cpl:{$campaign['id']}", 'campaign_id' => $campaign['id'],
                    'severity' => 'warning', 'title' => 'Custo por lead acima da média', 'description' => $campaign['name'],
                ];
            }
        }
        return $alerts;
    }

    /**
     * Monta o painel inteiro a partir de campanhas + inserções JÁ carregadas (cobrindo do
     * início do período anterior até ontem). Espelha loadDashboard() do protótipo.
     */
    public static function load(array $campaigns, array $insights, int $days, string $today): array
    {
        $ranges = self::periodRanges($days, $today);
        $current = $ranges['current'];
        $previous = $ranges['previous'];

        $currentInsights = self::inRange($insights, $current);
        $totals = self::deriveMetrics(self::sumInsights($currentInsights));
        $previousTotals = self::deriveMetrics(self::sumInsights(self::inRange($insights, $previous)));
        $rows = self::campaignRows($campaigns, $currentInsights);

        return [
            'range'           => $current,
            'totals'          => $totals,
            'previous_totals' => $previousTotals,
            'series'          => self::dailySeries($currentInsights, $current),
            'rows'            => $rows,
            'alerts'          => self::evaluateAlerts($rows, $totals['cost_per_lead_cents']),
        ];
    }
}
