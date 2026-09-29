<?php

namespace App\Services\Marketing;

/**
 * Regras de sugestão de otimização — porta fiel de
 * ads-platform/src/lib/optimization/rules.ts. Só SUGERE; nunca executa nada sozinha — vira
 * ação de verdade só depois de passar pela fila de aprovação (QueueService). No máximo uma
 * sugestão por campanha: a primeira regra que casar vence, sempre nesta ordem
 * (pausar > reduzir orçamento > aumentar orçamento).
 */
class Rules
{
    public const PAUSE_NO_LEADS_MIN_SPEND_CENTS = 5_000;
    public const HIGH_CPL_FACTOR = 1.5;
    public const LOW_CPL_FACTOR = 0.6;
    public const LOW_CPL_MIN_LEADS = 5;
    public const SCALE_MIN_BUDGET_USAGE = 0.8;
    public const BUDGET_STEP = 0.2;
    public const MIN_DAILY_BUDGET_CENTS = 500;

    /**
     * @param array $rows mesmo formato de Dashboard::campaignRows(): [['campaign'=>[...], 'metrics'=>[...]], ...]
     * @return array<int, array{campaign_id:int, rule_id:string, action_type:string, payload:array, reason:string}>
     */
    public static function suggest(array $rows, ?int $accountCostPerLeadCents, int $windowDays): array
    {
        $sugestoes = [];
        foreach ($rows as $row) {
            $campaign = $row['campaign'];
            $metrics = $row['metrics'];
            if ($campaign['status'] !== 'active') continue;

            if ($metrics['leads'] === 0 && $metrics['spend_cents'] >= self::PAUSE_NO_LEADS_MIN_SPEND_CENTS) {
                $sugestoes[] = [
                    'campaign_id' => $campaign['id'],
                    'rule_id'     => 'pause-no-leads',
                    'action_type' => 'pause_campaign',
                    'payload'     => [],
                    'reason'      => sprintf('Gastou %s nos últimos %d dias sem gerar nenhum lead.', Money::formatCents($metrics['spend_cents']), $windowDays),
                ];
                continue;
            }

            $budget = $campaign['daily_budget_cents'];
            $cpl = $metrics['cost_per_lead_cents'];
            if ($budget === null || $cpl === null || $accountCostPerLeadCents === null) continue;

            if ($cpl > $accountCostPerLeadCents * self::HIGH_CPL_FACTOR) {
                $toCents = max(self::MIN_DAILY_BUDGET_CENTS, (int) round($budget * (1 - self::BUDGET_STEP)));
                if ($toCents < $budget) {
                    $sugestoes[] = [
                        'campaign_id' => $campaign['id'],
                        'rule_id'     => 'reduce-budget-high-cpl',
                        'action_type' => 'update_daily_budget',
                        'payload'     => ['daily_budget_cents' => $toCents, 'previous_daily_budget_cents' => $budget],
                        'reason'      => sprintf(
                            'Custo por lead de %s, acima de %sx a média da conta (%s).',
                            Money::formatCents($cpl), self::formatarFator(self::HIGH_CPL_FACTOR), Money::formatCents($accountCostPerLeadCents)
                        ),
                    ];
                }
                continue;
            }

            $usoOrcamento = $budget > 0 ? $metrics['spend_cents'] / ($budget * $windowDays) : 0.0;
            if (
                $metrics['leads'] >= self::LOW_CPL_MIN_LEADS
                && $cpl <= $accountCostPerLeadCents * self::LOW_CPL_FACTOR
                && $usoOrcamento >= self::SCALE_MIN_BUDGET_USAGE
            ) {
                $sugestoes[] = [
                    'campaign_id' => $campaign['id'],
                    'rule_id'     => 'increase-budget-low-cpl',
                    'action_type' => 'update_daily_budget',
                    'payload'     => ['daily_budget_cents' => (int) round($budget * (1 + self::BUDGET_STEP)), 'previous_daily_budget_cents' => $budget],
                    'reason'      => sprintf(
                        'Custo por lead de %s, bem abaixo da média da conta (%s), usando quase todo o orçamento.',
                        Money::formatCents($cpl), Money::formatCents($accountCostPerLeadCents)
                    ),
                ];
            }
        }
        return $sugestoes;
    }

    /** "1,5" em vez de "1,50" — mesmo efeito de Intl.NumberFormat(maximumFractionDigits:2) do TS. */
    private static function formatarFator(float $fator): string
    {
        $s = rtrim(rtrim(number_format($fator, 2, '.', ''), '0'), '.');
        return str_replace('.', ',', $s);
    }
}
