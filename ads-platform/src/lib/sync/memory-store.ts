import type { PlatformCampaign, PlatformInsight } from "@/lib/ads/platform";
import type { SyncAccount, SyncStore, SyncSummary } from "./store";

/** In-memory SyncStore for tests. */
export class MemorySyncStore implements SyncStore {
  accounts: SyncAccount[] = [];
  campaigns = new Map<string, PlatformCampaign & { id: string; accountId: string }>();
  insights = new Map<string, PlatformInsight & { campaignId: string }>();
  syncs: Array<{ accountId: string; at: Date; summary: SyncSummary }> = [];
  private nextId = 1;

  async listActiveAccounts(filter?: { organizationId?: string }) {
    return this.accounts.filter((a) => !filter?.organizationId || a.organizationId === filter.organizationId);
  }

  async upsertCampaigns(account: SyncAccount, campaigns: PlatformCampaign[]) {
    const ids = new Map<string, string>();
    for (const campaign of campaigns) {
      const key = `${account.id}:${campaign.externalId}`;
      const id = this.campaigns.get(key)?.id ?? `c${this.nextId++}`;
      this.campaigns.set(key, { ...campaign, id, accountId: account.id });
      ids.set(campaign.externalId, id);
    }
    return ids;
  }

  async upsertInsights(_account: SyncAccount, rows: Array<PlatformInsight & { campaignId: string }>) {
    for (const row of rows) this.insights.set(`${row.campaignId}:${row.date}`, row);
  }

  async markSynced(account: SyncAccount, at: Date, summary: SyncSummary) {
    this.syncs.push({ accountId: account.id, at, summary });
    const stored = this.accounts.find((a) => a.id === account.id);
    if (stored) stored.lastSyncedAt = at.toISOString();
  }
}
