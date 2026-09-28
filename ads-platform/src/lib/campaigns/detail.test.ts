import { describe, expect, it } from "vitest";
import type { DailyInsight } from "@/lib/ads/types";
import { buildCampaignDetail, matchesStatusFilter, parseStatusFilter } from "./detail";

const insight = (date: string, spendCents: number, leads: number): DailyInsight => ({
  campaignId: "c1",
  date,
  spendCents,
  impressions: 1_000,
  clicks: 20,
  leads,
});

describe("buildCampaignDetail", () => {
  // Today 2026-09-28 → 7-day period 21–27, previous 14–20.
  const insights = [
    insight("2026-09-14", 1_000, 1),
    insight("2026-09-21", 2_000, 1),
    insight("2026-09-27", 3_000, 3),
    insight("2026-09-28", 9_999, 9), // today: incomplete, excluded
  ];
  const detail = buildCampaignDetail(insights, 7, "2026-09-28");

  it("totals the period and the previous one", () => {
    expect(detail.range).toEqual({ from: "2026-09-21", to: "2026-09-27" });
    expect(detail.totals.spendCents).toBe(5_000);
    expect(detail.totals.leads).toBe(4);
    expect(detail.totals.costPerLeadCents).toBe(1_250);
    expect(detail.previousTotals.spendCents).toBe(1_000);
  });

  it("lists every day, most recent first, zero-filled", () => {
    expect(detail.daily.map((d) => d.date)).toEqual([
      "2026-09-27",
      "2026-09-26",
      "2026-09-25",
      "2026-09-24",
      "2026-09-23",
      "2026-09-22",
      "2026-09-21",
    ]);
    expect(detail.daily[0]).toMatchObject({ spendCents: 3_000, leads: 3, costPerLeadCents: 1_000 });
    expect(detail.daily[1]).toMatchObject({ spendCents: 0, leads: 0, costPerLeadCents: null });
  });

  it("keeps the chart series in chronological order", () => {
    expect(detail.series.map((p) => p.date)[0]).toBe("2026-09-21");
    expect(detail.series.at(-1)).toEqual({ date: "2026-09-27", spendCents: 3_000, leads: 3 });
  });
});

describe("status filter", () => {
  it("parses and applies", () => {
    expect(parseStatusFilter("ativas")).toBe("ativas");
    expect(parseStatusFilter(["pausadas"])).toBe("pausadas");
    expect(parseStatusFilter("x")).toBe("todas");
    expect(matchesStatusFilter("active", "ativas")).toBe(true);
    expect(matchesStatusFilter("paused", "ativas")).toBe(false);
    expect(matchesStatusFilter("archived", "todas")).toBe(true);
  });
});
