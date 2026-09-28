import { beforeEach, describe, expect, it, vi } from "vitest";
import { FakeAdPlatform, resetFakePlatformState } from "@/lib/ads/fake-platform";
import type { AdPlatform } from "@/lib/ads/platform";
import type { Campaign, DailyInsight } from "@/lib/ads/types";
import type { DashboardDataSource } from "@/lib/dashboard/data-source";
import { eachDay } from "@/lib/dates";
import { executeApprovedRequests } from "./execute";
import { generateSuggestions } from "./generate";
import { MemoryQueueStore } from "./memory-store";

// 12:00 UTC on Sep 28 → today is Sep 28 in São Paulo; window is Sep 21–27.
const NOW = new Date("2026-09-28T12:00:00Z");

const campaign = (id: string, budget = 4_000): Campaign => ({
  id,
  platform: "fake",
  name: id,
  status: "active",
  dailyBudgetCents: budget,
});

function source(campaigns: Campaign[], daily: Record<string, { spend: number; leads: number }>): DashboardDataSource {
  return {
    listCampaigns: async () => campaigns,
    getDailyInsights: async ({ from, to }) =>
      eachDay(from, to).flatMap((date): DailyInsight[] =>
        Object.entries(daily).map(([campaignId, d]) => ({
          campaignId,
          date,
          spendCents: d.spend,
          impressions: 1_000,
          clicks: 10,
          leads: d.leads,
        })),
      ),
  };
}

// Per day: "good" R$ 38 / 4 leads (CPL 9,50); "bad" R$ 30 / 0 leads; account CPL ≈ 17,00.
const scenario = () =>
  source([campaign("good"), campaign("bad", 3_000)], { good: { spend: 3_800, leads: 4 }, bad: { spend: 3_000, leads: 0 } });

describe("generateSuggestions", () => {
  it("queues one pending request per matching rule", async () => {
    const queue = new MemoryQueueStore();
    const count = await generateSuggestions({ source: scenario(), queue, organizationId: "org1", now: NOW });
    expect(count).toBe(2);
    expect(queue.requests.map((r) => [r.campaignId, r.actionType, r.status])).toEqual([
      ["good", "update_daily_budget", "pending"],
      ["bad", "pause_campaign", "pending"],
    ]);
    expect(queue.requests[0]?.payload).toEqual({ daily_budget_cents: 4_800, previous_daily_budget_cents: 4_000 });
  });

  it("does not duplicate pending suggestions", async () => {
    const queue = new MemoryQueueStore();
    await generateSuggestions({ source: scenario(), queue, organizationId: "org1", now: NOW });
    expect(await generateSuggestions({ source: scenario(), queue, organizationId: "org1", now: NOW })).toBe(0);
    expect(queue.requests).toHaveLength(2);
  });

  it("respects a recent rejection but proposes again after the cooldown", async () => {
    const queue = new MemoryQueueStore();
    await generateSuggestions({ source: scenario(), queue, organizationId: "org1", now: NOW });
    for (const r of queue.requests) Object.assign(r, { status: "rejected", decidedAt: NOW });
    expect(await generateSuggestions({ source: scenario(), queue, organizationId: "org1", now: NOW })).toBe(0);
    const later = new Date(NOW.getTime() + 8 * 86_400_000);
    expect(await generateSuggestions({ source: scenario(), queue, organizationId: "org1", now: later })).toBe(2);
  });
});

describe("executeApprovedRequests", () => {
  beforeEach(() => resetFakePlatformState());

  async function setup() {
    const queue = new MemoryQueueStore();
    await generateSuggestions({ source: scenario(), queue, organizationId: "org1", now: NOW });
    queue.targets.set("good", { platform: "fake", accountExternalId: "demo_1", campaignExternalId: "demo_1_tv" });
    queue.targets.set("bad", { platform: "fake", accountExternalId: "demo_1", campaignExternalId: "demo_1_gel" });
    return queue;
  }

  const spyPlatform = () => {
    const platform = new FakeAdPlatform();
    return {
      platform,
      setStatus: vi.spyOn(platform, "setCampaignStatus"),
      setBudget: vi.spyOn(platform, "setDailyBudget"),
    };
  };

  it("never executes pending or rejected requests", async () => {
    const queue = await setup();
    queue.requests[1]!.status = "rejected";
    const { platform, setStatus, setBudget } = spyPlatform();
    const results = await executeApprovedRequests({ queue, platformFor: () => platform, dryRun: false });
    expect(results).toEqual([]);
    expect(setStatus).not.toHaveBeenCalled();
    expect(setBudget).not.toHaveBeenCalled();
    expect(queue.requests.map((r) => r.status)).toEqual(["pending", "rejected"]);
  });

  it("in DRY_RUN records the execution without calling the platform", async () => {
    const queue = await setup();
    for (const r of queue.requests) r.status = "approved";
    const { platform, setStatus, setBudget } = spyPlatform();
    const results = await executeApprovedRequests({ queue, platformFor: () => platform, dryRun: true, now: () => NOW });
    expect(results.every((r) => r.status === "executed" && r.dryRun)).toBe(true);
    expect(setStatus).not.toHaveBeenCalled();
    expect(setBudget).not.toHaveBeenCalled();
    expect(queue.campaignChanges).toEqual([]);
    expect(queue.requests.every((r) => r.status === "executed" && r.dryRun === true)).toBe(true);
  });

  it("outside DRY_RUN applies approved changes on the platform and locally", async () => {
    const queue = await setup();
    for (const r of queue.requests) r.status = "approved";
    const { platform, setStatus, setBudget } = spyPlatform();
    await executeApprovedRequests({ queue, platformFor: () => platform, dryRun: false });
    expect(setBudget).toHaveBeenCalledWith({ externalId: "demo_1" }, "demo_1_tv", 4_800);
    expect(setStatus).toHaveBeenCalledWith({ externalId: "demo_1" }, "demo_1_gel", "paused");
    expect(queue.campaignChanges).toEqual([
      { campaignId: "good", change: { dailyBudgetCents: 4_800 } },
      { campaignId: "bad", change: { status: "paused" } },
    ]);
  });

  it("can execute a single request", async () => {
    const queue = await setup();
    for (const r of queue.requests) r.status = "approved";
    const results = await executeApprovedRequests({
      queue,
      platformFor: () => new FakeAdPlatform(),
      dryRun: true,
      filter: { requestId: queue.requests[1]!.id },
    });
    expect(results).toHaveLength(1);
    expect(queue.requests.map((r) => r.status)).toEqual(["approved", "executed"]);
  });

  it("marks failures with the reason and keeps going", async () => {
    const queue = await setup();
    for (const r of queue.requests) r.status = "approved";
    queue.requests[0]!.payload = { daily_budget_cents: 12.5 };
    const failing: AdPlatform = {
      ...new FakeAdPlatform(),
      id: "fake",
      listCampaigns: async () => [],
      getDailyInsights: async () => [],
      setCampaignStatus: async () => {
        throw new Error("API indisponível");
      },
      setDailyBudget: async () => {},
    };
    const results = await executeApprovedRequests({ queue, platformFor: () => failing, dryRun: false });
    expect(results.map((r) => r.status)).toEqual(["failed", "failed"]);
    expect(queue.requests[1]?.error).toBe("API indisponível");
    expect(queue.campaignChanges).toEqual([]);
  });

  it("fails when the campaign no longer exists", async () => {
    const queue = await setup();
    queue.requests[0]!.status = "approved";
    queue.targets.clear();
    const [result] = await executeApprovedRequests({ queue, platformFor: () => new FakeAdPlatform(), dryRun: true });
    expect(result).toMatchObject({ status: "failed", error: "Campanha não encontrada." });
  });
});
