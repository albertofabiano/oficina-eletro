import { describe, expect, it } from "vitest";
import type { Campaign, CampaignStatus } from "@/lib/ads/types";
import { deriveMetrics, type CampaignRow } from "@/lib/dashboard/metrics";
import { describeAction } from "./describe";
import { parseActionPayload, toStoredPayload } from "./payload";
import { suggestOptimizations } from "./rules";

const row = (
  id: string,
  totals: { spendCents: number; leads: number },
  options: { status?: CampaignStatus; budget?: number | null } = {},
): CampaignRow => {
  const campaign: Campaign = {
    id,
    platform: "fake",
    name: id,
    status: options.status ?? "active",
    dailyBudgetCents: options.budget === undefined ? 4_000 : options.budget,
  };
  return { campaign, metrics: deriveMetrics({ impressions: 10_000, clicks: 100, ...totals }) };
};

describe("suggestOptimizations", () => {
  it("suggests pausing active campaigns that spend without leads", () => {
    const [s] = suggestOptimizations([row("gel", { spendCents: 15_000, leads: 0 })], 2_000, 7);
    expect(s).toMatchObject({ campaignId: "gel", ruleId: "pause-no-leads", action: { type: "pause_campaign" } });
    expect(s?.reason.replace(/\s/g, " ")).toContain("R$ 150,00");
  });

  it("does not pause below the minimum spend or paused campaigns", () => {
    expect(suggestOptimizations([row("a", { spendCents: 4_999, leads: 0 })], 2_000, 7)).toEqual([]);
    expect(suggestOptimizations([row("a", { spendCents: 9_000, leads: 0 }, { status: "paused" })], 2_000, 7)).toEqual([]);
  });

  it("cuts the budget 20% when CPL is above 1.5x the account average", () => {
    const [s] = suggestOptimizations([row("cel", { spendCents: 20_000, leads: 2 }, { budget: 3_500 })], 2_000, 7);
    expect(s).toMatchObject({
      ruleId: "reduce-budget-high-cpl",
      action: { type: "update_daily_budget", fromCents: 3_500, toCents: 2_800 },
    });
  });

  it("never cuts below the minimum daily budget", () => {
    expect(suggestOptimizations([row("x", { spendCents: 20_000, leads: 1 }, { budget: 500 })], 2_000, 7)).toEqual([]);
    const [s] = suggestOptimizations([row("x", { spendCents: 20_000, leads: 1 }, { budget: 600 })], 2_000, 7);
    expect(s?.action).toEqual({ type: "update_daily_budget", fromCents: 600, toCents: 500 });
  });

  it("scales efficient campaigns that use most of their budget", () => {
    // Budget R$ 40/day × 7 = R$ 280; spent R$ 260 (93%), CPL R$ 10 vs account R$ 20.
    const [s] = suggestOptimizations([row("tv", { spendCents: 26_000, leads: 26 })], 2_000, 7);
    expect(s).toMatchObject({
      ruleId: "increase-budget-low-cpl",
      action: { type: "update_daily_budget", fromCents: 4_000, toCents: 4_800 },
    });
  });

  it("does not scale with few leads or unused budget", () => {
    expect(suggestOptimizations([row("tv", { spendCents: 4_000, leads: 4 })], 2_000, 7)).toEqual([]);
    expect(suggestOptimizations([row("tv", { spendCents: 10_000, leads: 10 })], 2_000, 7)).toEqual([]);
  });

  it("gives at most one suggestion per campaign and skips when there is no baseline", () => {
    expect(suggestOptimizations([row("a", { spendCents: 20_000, leads: 2 })], null, 7)).toEqual([]);
    const suggestions = suggestOptimizations(
      [row("a", { spendCents: 9_000, leads: 0 }), row("b", { spendCents: 20_000, leads: 2 })],
      2_000,
      7,
    );
    expect(suggestions.map((s) => s.campaignId)).toEqual(["a", "b"]);
  });

  it("produces integer cents only", () => {
    const [s] = suggestOptimizations([row("c", { spendCents: 20_000, leads: 2 }, { budget: 3_333 })], 2_000, 7);
    expect(s?.action).toEqual({ type: "update_daily_budget", fromCents: 3_333, toCents: 2_666 });
  });
});

describe("action payloads", () => {
  it("round-trips budget changes", () => {
    const action = { type: "update_daily_budget", fromCents: 3_500, toCents: 2_800 } as const;
    expect(parseActionPayload("update_daily_budget", toStoredPayload(action))).toEqual(action);
  });

  it("rejects invalid stored budgets", () => {
    expect(() => parseActionPayload("update_daily_budget", { daily_budget_cents: 12.5 })).toThrow();
    expect(() => parseActionPayload("update_daily_budget", { daily_budget_cents: -1 })).toThrow();
    expect(() => parseActionPayload("update_daily_budget", {})).toThrow();
  });

  it("describes actions in Portuguese", () => {
    expect(describeAction({ type: "pause_campaign" })).toBe("Pausar campanha");
    expect(describeAction({ type: "update_daily_budget", fromCents: 3_500, toCents: 2_800 }).replace(/\s/g, " ")).toBe(
      "Reduzir orçamento diário de R$ 35,00 para R$ 28,00",
    );
  });
});
