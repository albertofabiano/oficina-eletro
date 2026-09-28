import type { PlatformCampaign, PlatformInsight } from "@/lib/ads/platform";
import type { PlatformId } from "@/lib/ads/types";

export interface SyncAccount {
  id: string;
  organizationId: string;
  platform: PlatformId;
  externalId: string;
  lastSyncedAt: string | null;
}

export interface SyncSummary {
  from: string;
  to: string;
  campaigns: number;
  insightRows: number;
}

/** Persistence used by the collector; Supabase in production, in-memory in tests. */
export interface SyncStore {
  listActiveAccounts(filter?: { organizationId?: string }): Promise<SyncAccount[]>;
  /** Upserts by (account, external id) and returns external id → internal campaign id. */
  upsertCampaigns(account: SyncAccount, campaigns: PlatformCampaign[]): Promise<Map<string, string>>;
  /** Upserts by (campaign, date): re-collected days overwrite the previous values. */
  upsertInsights(account: SyncAccount, rows: Array<PlatformInsight & { campaignId: string }>): Promise<void>;
  /** Records a successful collection and clears the previous error. */
  markSynced(account: SyncAccount, at: Date, summary: SyncSummary): Promise<void>;
  /** Keeps the last collection error so the settings page can show it. */
  markFailed(account: SyncAccount, error: string): Promise<void>;
}
