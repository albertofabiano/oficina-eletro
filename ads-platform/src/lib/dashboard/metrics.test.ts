import { describe, expect, it } from "vitest";
import type { Campaign, DailyInsight } from "@/lib/ads/types";
import { campaignRows, dailySeries, deriveMetrics, percentChange, sumInsights } from "./metrics";

const insight = (overrides: Partial<DailyInsight>): DailyInsight => ({
  campaignId: "a",
  date: "2026-09-01",
  spendCents: 0,
  impressions: 0,
  clicks: 0,
  leads: 0,
  ...overrides,
});

const campaign = (id: string): Campaign => ({
  id,
  platform: "meta",
  name: id,
  status: "active",
  dailyBudgetCents: 1000,
});

describe("deriveMetrics", () => {
  it("computes cost metrics in integer cents", () => {
    const metrics = deriveMetrics(
      sumInsights([
        insight({ spendCents: 5000, impressions: 3000, clicks: 30, leads: 2 }),
        insight({ spendCents: 5001, impressions: 1000, clicks: 10, leads: 1 }),
      ]),
    );
    expect(metrics.spendCents).toBe(10001);
    expect(metrics.costPerLeadCents).toBe(3334);
    expect(metrics.costPerClickCents).toBe(250);
    expect(metrics.cpmCents).toBe(2500);
    expect(metrics.ctr).toBeCloseTo(0.01);
    expect(Number.isInteger(metrics.costPerLeadCents)).toBe(true);
  });

  it("returns null instead of dividing by zero", () => {
    const metrics = deriveMetrics(sumInsights([]));
    expect(metrics.costPerLeadCents).toBeNull();
    expect(metrics.ctr).toBeNull();
  });
});

describe("percentChange", () => {
  it("handles missing baselines", () => {
    expect(percentChange(150, 100)).toBeCloseTo(0.5);
    expect(percentChange(10, 0)).toBeNull();
    expect(percentChange(null, 100)).toBeNull();
  });
});

describe("dailySeries", () => {
  it("zero-fills missing days and sums campaigns", () => {
    const series = dailySeries(
      [
        insight({ date: "2026-09-01", spendCents: 100, leads: 1 }),
        insight({ campaignId: "b", date: "2026-09-01", spendCents: 50 }),
        insight({ date: "2026-09-05", spendCents: 999 }),
      ],
      { from: "2026-09-01", to: "2026-09-03" },
    );
    expect(series).toEqual([
      { date: "2026-09-01", spendCents: 150, leads: 1 },
      { date: "2026-09-02", spendCents: 0, leads: 0 },
      { date: "2026-09-03", spendCents: 0, leads: 0 },
    ]);
  });
});

describe("campaignRows", () => {
  it("groups by campaign and sorts by spend", () => {
    const rows = campaignRows(
      [campaign("a"), campaign("b"), campaign("c")],
      [insight({ campaignId: "a", spendCents: 100 }), insight({ campaignId: "b", spendCents: 300 })],
    );
    expect(rows.map((row) => [row.campaign.id, row.metrics.spendCents])).toEqual([
      ["b", 300],
      ["a", 100],
      ["c", 0],
    ]);
  });
});
