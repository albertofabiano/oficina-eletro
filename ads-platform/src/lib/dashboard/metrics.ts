import type { Campaign, DailyInsight } from "@/lib/ads/types";
import { eachDay, type IsoDate } from "@/lib/dates";
import { divideCents, type Cents } from "@/lib/money";
import type { DateRange } from "./period";

export interface Totals {
  spendCents: Cents;
  impressions: number;
  clicks: number;
  leads: number;
}

export interface DerivedMetrics extends Totals {
  costPerLeadCents: Cents | null;
  costPerClickCents: Cents | null;
  cpmCents: Cents | null;
  /** Ratio between 0 and 1. */
  ctr: number | null;
}

export const EMPTY_TOTALS: Totals = { spendCents: 0, impressions: 0, clicks: 0, leads: 0 };

export function sumInsights(insights: readonly DailyInsight[]): Totals {
  return insights.reduce<Totals>(
    (acc, row) => ({
      spendCents: acc.spendCents + row.spendCents,
      impressions: acc.impressions + row.impressions,
      clicks: acc.clicks + row.clicks,
      leads: acc.leads + row.leads,
    }),
    EMPTY_TOTALS,
  );
}

export function deriveMetrics(totals: Totals): DerivedMetrics {
  return {
    ...totals,
    costPerLeadCents: divideCents(totals.spendCents, totals.leads),
    costPerClickCents: divideCents(totals.spendCents, totals.clicks),
    cpmCents: divideCents(totals.spendCents * 1000, totals.impressions),
    ctr: totals.impressions === 0 ? null : totals.clicks / totals.impressions,
  };
}

export function inRange(insights: readonly DailyInsight[], range: DateRange): DailyInsight[] {
  return insights.filter((row) => row.date >= range.from && row.date <= range.to);
}

/** Relative change; null when there is no baseline to compare against. */
export function percentChange(current: number | null, previous: number | null): number | null {
  if (current === null || previous === null || previous === 0) return null;
  return (current - previous) / previous;
}

export interface DailyPoint {
  date: IsoDate;
  spendCents: Cents;
  leads: number;
}

/** One point per day in the range, zero-filled for days without data. */
export function dailySeries(insights: readonly DailyInsight[], range: DateRange): DailyPoint[] {
  const byDay = new Map<IsoDate, DailyPoint>();
  for (const date of eachDay(range.from, range.to)) {
    byDay.set(date, { date, spendCents: 0, leads: 0 });
  }
  for (const row of insights) {
    const point = byDay.get(row.date);
    if (!point) continue;
    point.spendCents += row.spendCents;
    point.leads += row.leads;
  }
  return [...byDay.values()];
}

export interface CampaignRow {
  campaign: Campaign;
  metrics: DerivedMetrics;
}

/** Campaigns with their metrics, highest spend first. */
export function campaignRows(campaigns: readonly Campaign[], insights: readonly DailyInsight[]): CampaignRow[] {
  const grouped = new Map<string, DailyInsight[]>();
  for (const row of insights) {
    const list = grouped.get(row.campaignId) ?? [];
    list.push(row);
    grouped.set(row.campaignId, list);
  }
  return campaigns
    .map((campaign) => ({
      campaign,
      metrics: deriveMetrics(sumInsights(grouped.get(campaign.id) ?? [])),
    }))
    .sort((a, b) => b.metrics.spendCents - a.metrics.spendCents);
}
