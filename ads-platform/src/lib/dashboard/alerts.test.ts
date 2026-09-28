import { describe, expect, it } from "vitest";
import type { Campaign, CampaignStatus } from "@/lib/ads/types";
import { evaluateAlerts } from "./alerts";
import { deriveMetrics, type CampaignRow } from "./metrics";

const row = (
  id: string,
  totals: { spendCents: number; leads: number },
  status: CampaignStatus = "active",
): CampaignRow => {
  const campaign: Campaign = { id, platform: "meta", name: `Campanha ${id}`, status, dailyBudgetCents: 2000 };
  return { campaign, metrics: deriveMetrics({ impressions: 1000, clicks: 10, ...totals }) };
};

describe("evaluateAlerts", () => {
  it("flags active campaigns spending without leads", () => {
    const alerts = evaluateAlerts([row("a", { spendCents: 6000, leads: 0 })], 2000);
    expect(alerts).toEqual([expect.objectContaining({ id: "no-leads:a", severity: "critical" })]);
  });

  it("ignores small spend without leads", () => {
    expect(evaluateAlerts([row("a", { spendCents: 4999, leads: 0 })], 2000)).toEqual([]);
  });

  it("flags CPL above 1.5x the account average", () => {
    const alerts = evaluateAlerts(
      [row("cheap", { spendCents: 10000, leads: 10 }), row("pricey", { spendCents: 10000, leads: 3 })],
      2000,
    );
    expect(alerts.map((alert) => alert.id)).toEqual(["high-cpl:pricey"]);
  });

  it("does not flag CPL exactly at the threshold", () => {
    expect(evaluateAlerts([row("a", { spendCents: 3000, leads: 1 })], 2000)).toEqual([]);
  });

  it("skips paused campaigns", () => {
    expect(evaluateAlerts([row("a", { spendCents: 9000, leads: 0 }, "paused")], 2000)).toEqual([]);
  });

  it("skips the CPL rule when the account has no leads", () => {
    expect(evaluateAlerts([row("a", { spendCents: 3000, leads: 1 })], null)).toEqual([]);
  });
});
