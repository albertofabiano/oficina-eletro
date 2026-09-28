import { beforeEach, describe, expect, it, vi } from "vitest";
import { FakeAdPlatform, resetFakePlatformState } from "./fake-platform";
import { UsageThrottle } from "./rate-limit";

const account = { externalId: "demo_1" };
const range = { from: "2026-09-01", to: "2026-09-07" };

beforeEach(() => resetFakePlatformState());

describe("FakeAdPlatform", () => {
  it("lists campaigns with ids scoped to the account", async () => {
    const campaigns = await new FakeAdPlatform().listCampaigns(account);
    expect(campaigns).toHaveLength(4);
    expect(campaigns.every((c) => c.externalId.startsWith("demo_1_"))).toBe(true);
  });

  it("returns one integer-cents row per campaign and day, deterministically", async () => {
    const first = await new FakeAdPlatform().getDailyInsights(account, range);
    const second = await new FakeAdPlatform().getDailyInsights(account, range);
    expect(first).toHaveLength(4 * 7);
    expect(first).toEqual(second);
    expect(first.every((row) => Number.isInteger(row.spendCents) && row.spendCents >= 0)).toBe(true);
  });

  it("paused campaigns spend nothing", async () => {
    const rows = await new FakeAdPlatform().getDailyInsights(account, range);
    expect(rows.filter((r) => r.campaignExternalId === "demo_1_rmk").every((r) => r.spendCents === 0)).toBe(true);
  });

  it("applies writes like a real platform", async () => {
    const platform = new FakeAdPlatform();
    await platform.setCampaignStatus(account, "demo_1_tv", "paused");
    await platform.setDailyBudget(account, "demo_1_cel", 5_000);
    const campaigns = await platform.listCampaigns(account);
    expect(campaigns.find((c) => c.externalId === "demo_1_tv")?.status).toBe("paused");
    expect(campaigns.find((c) => c.externalId === "demo_1_cel")?.dailyBudgetCents).toBe(5_000);
    await expect(platform.setDailyBudget(account, "demo_1_cel", 12.5)).rejects.toThrow();
    await expect(platform.setCampaignStatus(account, "other_tv", "paused")).rejects.toThrow();
  });

  it("slows down when simulated usage passes 75%", async () => {
    const sleep = vi.fn(async () => {});
    const platform = new FakeAdPlatform(new UsageThrottle(sleep, 1000), 40);
    await platform.listCampaigns(account); // usage 40%
    await platform.listCampaigns(account); // usage 80% recorded after this call
    expect(sleep).not.toHaveBeenCalled();
    await platform.listCampaigns(account);
    expect(sleep).toHaveBeenCalledWith(200);
  });
});
