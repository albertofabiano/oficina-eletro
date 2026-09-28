import type { CampaignStatus, DailyInsight } from "@/lib/ads/types";
import { eachDay, type IsoDate } from "@/lib/dates";
import { dailySeries, deriveMetrics, inRange, sumInsights, type DerivedMetrics } from "@/lib/dashboard/metrics";
import { periodRanges, type PeriodDays } from "@/lib/dashboard/period";

export interface DailyRow extends DerivedMetrics {
  date: IsoDate;
}

/** Metrics for a single campaign over the selected period, most recent day first in `daily`. */
export function buildCampaignDetail(insights: readonly DailyInsight[], days: PeriodDays, today: IsoDate) {
  const { current, previous } = periodRanges(days, today);
  const currentInsights = inRange(insights, current);

  const byDay = new Map<IsoDate, DailyInsight[]>();
  for (const row of currentInsights) {
    const list = byDay.get(row.date) ?? [];
    list.push(row);
    byDay.set(row.date, list);
  }
  const daily: DailyRow[] = eachDay(current.from, current.to)
    .reverse()
    .map((date) => ({ date, ...deriveMetrics(sumInsights(byDay.get(date) ?? [])) }));

  return {
    range: current,
    totals: deriveMetrics(sumInsights(currentInsights)),
    previousTotals: deriveMetrics(sumInsights(inRange(insights, previous))),
    series: dailySeries(currentInsights, current),
    daily,
  };
}

export type StatusFilter = "todas" | "ativas" | "pausadas";

export function parseStatusFilter(raw: unknown): StatusFilter {
  const value = Array.isArray(raw) ? raw[0] : raw;
  return value === "ativas" || value === "pausadas" ? value : "todas";
}

export function matchesStatusFilter(status: CampaignStatus, filter: StatusFilter): boolean {
  if (filter === "ativas") return status === "active";
  if (filter === "pausadas") return status === "paused";
  return true;
}
