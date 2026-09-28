import { beforeEach, describe, expect, it } from "vitest";
import { FakeAdPlatform, resetFakePlatformState } from "@/lib/ads/fake-platform";
import type { AdPlatform } from "@/lib/ads/platform";
import { MemorySyncStore } from "./memory-store";
import type { SyncAccount } from "./store";
import { BACKFILL_DAYS, collectionRange, syncAdAccount, syncAllAccounts } from "./sync-account";

// 02:30 UTC on Sep 28 is still Sep 27 in São Paulo.
const NOW = new Date("2026-09-28T02:30:00Z");

const account = (overrides: Partial<SyncAccount> = {}): SyncAccount => ({
  id: "acc1",
  organizationId: "org1",
  platform: "fake",
  externalId: "demo_1",
  lastSyncedAt: null,
  ...overrides,
});

beforeEach(() => resetFakePlatformState());

describe("collectionRange", () => {
  it("backfills on the first run and re-collects 7 days afterwards", () => {
    expect(collectionRange("2026-09-27", null)).toEqual({ from: "2026-07-30", to: "2026-09-27" });
    expect(collectionRange("2026-09-27", "2026-09-26T09:00:00Z")).toEqual({ from: "2026-09-21", to: "2026-09-27" });
  });
});

describe("syncAdAccount", () => {
  it("imports history on the first run using the São Paulo calendar", async () => {
    const store = new MemorySyncStore();
    store.accounts = [account()];
    const summary = await syncAdAccount({ store, platform: new FakeAdPlatform(), account: store.accounts[0]!, now: NOW });
    expect(summary).toEqual({ from: "2026-07-30", to: "2026-09-27", campaigns: 4, insightRows: 4 * BACKFILL_DAYS });
    expect(store.accounts[0]!.lastSyncedAt).toBe(NOW.toISOString());
  });

  it("is idempotent: re-collecting overwrites instead of duplicating", async () => {
    const store = new MemorySyncStore();
    store.accounts = [account()];
    const platform = new FakeAdPlatform();
    await syncAdAccount({ store, platform, account: store.accounts[0]!, now: NOW });
    const before = store.insights.size;
    const second = await syncAdAccount({ store, platform, account: store.accounts[0]!, now: NOW });
    expect(second.insightRows).toBe(4 * 7);
    expect(store.insights.size).toBe(before);
    expect(store.campaigns.size).toBe(4);
  });

  it("updates campaign status and budget from the platform", async () => {
    const store = new MemorySyncStore();
    store.accounts = [account()];
    const platform = new FakeAdPlatform();
    await syncAdAccount({ store, platform, account: store.accounts[0]!, now: NOW });
    await platform.setCampaignStatus({ externalId: "demo_1" }, "demo_1_tv", "paused");
    await syncAdAccount({ store, platform, account: store.accounts[0]!, now: NOW });
    expect(store.campaigns.get("acc1:demo_1_tv")?.status).toBe("paused");
  });
});

describe("syncAllAccounts", () => {
  it("keeps going when one account fails and can filter by organization", async () => {
    const store = new MemorySyncStore();
    store.accounts = [account(), account({ id: "acc2", externalId: "broken" }), account({ id: "acc3", organizationId: "org2" })];
    const failing: AdPlatform = {
      ...new FakeAdPlatform(),
      id: "fake",
      listCampaigns: async () => {
        throw new Error("boom");
      },
      getDailyInsights: async () => [],
      setCampaignStatus: async () => {},
      setDailyBudget: async () => {},
    };
    const results = await syncAllAccounts({
      store,
      organizationId: "org1",
      platformFor: async (a) => (a.externalId === "broken" ? failing : new FakeAdPlatform()),
      now: NOW,
    });
    expect(results.map((r) => [r.accountId, r.ok])).toEqual([
      ["acc1", true],
      ["acc2", false],
    ]);
    expect(store.failures).toEqual([{ accountId: "acc2", error: "boom" }]);
  });
});
