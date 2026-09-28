import type { Cents } from "@/lib/money";
import type { CampaignRow } from "./metrics";

export type AlertSeverity = "warning" | "critical";

export interface DashboardAlert {
  id: string;
  campaignId: string;
  severity: AlertSeverity;
  title: string;
  description: string;
}

export interface AlertThresholds {
  /** Minimum spend without any lead before flagging the campaign. */
  noLeadsMinSpendCents: Cents;
  /** Campaign CPL above account CPL times this factor is flagged. */
  highCplFactor: number;
}

export const DEFAULT_THRESHOLDS: AlertThresholds = {
  noLeadsMinSpendCents: 5_000,
  highCplFactor: 1.5,
};

/**
 * Read-only alerts for the dashboard. They never act on campaigns: any
 * resulting change must go through the approval queue.
 */
export function evaluateAlerts(
  rows: readonly CampaignRow[],
  accountCostPerLeadCents: Cents | null,
  thresholds: AlertThresholds = DEFAULT_THRESHOLDS,
): DashboardAlert[] {
  const alerts: DashboardAlert[] = [];

  for (const { campaign, metrics } of rows) {
    if (campaign.status !== "active") continue;

    if (metrics.leads === 0 && metrics.spendCents >= thresholds.noLeadsMinSpendCents) {
      alerts.push({
        id: `no-leads:${campaign.id}`,
        campaignId: campaign.id,
        severity: "critical",
        title: "Gastando sem gerar leads",
        description: campaign.name,
      });
      continue;
    }

    if (
      accountCostPerLeadCents !== null &&
      metrics.costPerLeadCents !== null &&
      metrics.costPerLeadCents > accountCostPerLeadCents * thresholds.highCplFactor
    ) {
      alerts.push({
        id: `high-cpl:${campaign.id}`,
        campaignId: campaign.id,
        severity: "warning",
        title: "Custo por lead acima da média",
        description: campaign.name,
      });
    }
  }

  return alerts;
}
