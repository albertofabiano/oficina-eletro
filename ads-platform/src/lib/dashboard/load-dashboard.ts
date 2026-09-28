import type { DashboardDataSource } from "./data-source";
import { evaluateAlerts } from "./alerts";
import { campaignRows, dailySeries, deriveMetrics, inRange, sumInsights } from "./metrics";
import { periodRanges, type PeriodDays } from "./period";
import type { IsoDate } from "@/lib/dates";

export async function loadDashboard(source: DashboardDataSource, days: PeriodDays, today: IsoDate) {
  const { current, previous } = periodRanges(days, today);
  const [campaigns, insights] = await Promise.all([
    source.listCampaigns(),
    source.getDailyInsights({ from: previous.from, to: current.to }),
  ]);

  const currentInsights = inRange(insights, current);
  const totals = deriveMetrics(sumInsights(currentInsights));
  const previousTotals = deriveMetrics(sumInsights(inRange(insights, previous)));
  const rows = campaignRows(campaigns, currentInsights);

  return {
    range: current,
    totals,
    previousTotals,
    series: dailySeries(currentInsights, current),
    rows,
    alerts: evaluateAlerts(rows, totals.costPerLeadCents),
  };
}

export type DashboardData = Awaited<ReturnType<typeof loadDashboard>>;
