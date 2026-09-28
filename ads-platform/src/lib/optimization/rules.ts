import type { CampaignRow } from "@/lib/dashboard/metrics";
import { formatCents, type Cents } from "@/lib/money";

const factor = new Intl.NumberFormat("pt-BR", { maximumFractionDigits: 2 });
import type { Suggestion } from "./types";

export interface RuleThresholds {
  /** Spend without any lead in the window that justifies pausing. */
  pauseNoLeadsMinSpendCents: Cents;
  /** Campaign CPL above account CPL × this factor gets a budget cut. */
  highCplFactor: number;
  /** Campaign CPL at or below account CPL × this factor may get more budget. */
  lowCplFactor: number;
  /** Minimum leads before trusting a low CPL. */
  lowCplMinLeads: number;
  /** Share of the budget the campaign must be spending before scaling it. */
  scaleMinBudgetUsage: number;
  /** Relative budget change per suggestion (0.2 = 20%). */
  budgetStep: number;
  /** Never suggest a daily budget below this. */
  minDailyBudgetCents: Cents;
}

export const DEFAULT_RULE_THRESHOLDS: RuleThresholds = {
  pauseNoLeadsMinSpendCents: 5_000,
  highCplFactor: 1.5,
  lowCplFactor: 0.6,
  lowCplMinLeads: 5,
  scaleMinBudgetUsage: 0.8,
  budgetStep: 0.2,
  minDailyBudgetCents: 500,
};

/**
 * Turns the last `windowDays` of metrics into suggestions. At most one
 * suggestion per campaign; the first matching rule wins (pause > cut > scale).
 */
export function suggestOptimizations(
  rows: readonly CampaignRow[],
  accountCostPerLeadCents: Cents | null,
  windowDays: number,
  thresholds: RuleThresholds = DEFAULT_RULE_THRESHOLDS,
): Suggestion[] {
  const suggestions: Suggestion[] = [];

  for (const { campaign, metrics } of rows) {
    if (campaign.status !== "active") continue;

    if (metrics.leads === 0 && metrics.spendCents >= thresholds.pauseNoLeadsMinSpendCents) {
      suggestions.push({
        campaignId: campaign.id,
        ruleId: "pause-no-leads",
        action: { type: "pause_campaign" },
        reason: `Gastou ${formatCents(metrics.spendCents)} nos últimos ${windowDays} dias sem gerar nenhum lead.`,
      });
      continue;
    }

    const budget = campaign.dailyBudgetCents;
    const cpl = metrics.costPerLeadCents;
    if (budget === null || cpl === null || accountCostPerLeadCents === null) continue;

    if (cpl > accountCostPerLeadCents * thresholds.highCplFactor) {
      const toCents = Math.max(thresholds.minDailyBudgetCents, Math.round(budget * (1 - thresholds.budgetStep)));
      if (toCents < budget) {
        suggestions.push({
          campaignId: campaign.id,
          ruleId: "reduce-budget-high-cpl",
          action: { type: "update_daily_budget", fromCents: budget, toCents },
          reason: `Custo por lead de ${formatCents(cpl)}, acima de ${factor.format(thresholds.highCplFactor)}x a média da conta (${formatCents(accountCostPerLeadCents)}).`,
        });
      }
      continue;
    }

    const budgetUsage = metrics.spendCents / (budget * windowDays);
    if (
      metrics.leads >= thresholds.lowCplMinLeads &&
      cpl <= accountCostPerLeadCents * thresholds.lowCplFactor &&
      budgetUsage >= thresholds.scaleMinBudgetUsage
    ) {
      suggestions.push({
        campaignId: campaign.id,
        ruleId: "increase-budget-low-cpl",
        action: { type: "update_daily_budget", fromCents: budget, toCents: Math.round(budget * (1 + thresholds.budgetStep)) },
        reason: `Custo por lead de ${formatCents(cpl)}, bem abaixo da média da conta (${formatCents(accountCostPerLeadCents)}), usando quase todo o orçamento.`,
      });
    }
  }

  return suggestions;
}
