import type { PlatformCampaign, PlatformInsight } from "@/lib/ads/platform";
import type { AdminClient } from "@/lib/supabase/admin";
import type { SyncAccount, SyncStore, SyncSummary } from "./store";

const CHUNK = 500;

function chunks<T>(items: T[]): T[][] {
  const out: T[][] = [];
  for (let i = 0; i < items.length; i += CHUNK) out.push(items.slice(i, i + CHUNK));
  return out;
}

/** SyncStore backed by Supabase through the service-role client. */
export class SupabaseSyncStore implements SyncStore {
  constructor(private readonly db: AdminClient) {}

  async listActiveAccounts(filter?: { organizationId?: string }): Promise<SyncAccount[]> {
    let query = this.db
      .from("ad_accounts")
      .select("id, organization_id, platform, external_id, last_synced_at")
      .eq("status", "active");
    if (filter?.organizationId) query = query.eq("organization_id", filter.organizationId);
    const { data, error } = await query;
    if (error) throw error;
    return data.map((row) => ({
      id: row.id,
      organizationId: row.organization_id,
      platform: row.platform,
      externalId: row.external_id,
      lastSyncedAt: row.last_synced_at,
    }));
  }

  async upsertCampaigns(account: SyncAccount, campaigns: PlatformCampaign[]) {
    const ids = new Map<string, string>();
    if (campaigns.length === 0) return ids;
    const syncedAt = new Date().toISOString();
    const { data, error } = await this.db
      .from("campaigns")
      .upsert(
        campaigns.map((c) => ({
          organization_id: account.organizationId,
          ad_account_id: account.id,
          external_id: c.externalId,
          name: c.name,
          status: c.status,
          daily_budget_cents: c.dailyBudgetCents,
          synced_at: syncedAt,
        })),
        { onConflict: "ad_account_id,external_id" },
      )
      .select("id, external_id");
    if (error) throw error;
    for (const row of data) ids.set(row.external_id, row.id);
    return ids;
  }

  async upsertInsights(account: SyncAccount, rows: Array<PlatformInsight & { campaignId: string }>) {
    const collectedAt = new Date().toISOString();
    for (const batch of chunks(rows)) {
      const { error } = await this.db.from("daily_insights").upsert(
        batch.map((r) => ({
          campaign_id: r.campaignId,
          organization_id: account.organizationId,
          date: r.date,
          spend_cents: r.spendCents,
          impressions: r.impressions,
          clicks: r.clicks,
          leads: r.leads,
          collected_at: collectedAt,
        })),
        { onConflict: "campaign_id,date" },
      );
      if (error) throw error;
    }
  }

  async markSynced(account: SyncAccount, at: Date, summary: SyncSummary) {
    const { error } = await this.db
      .from("ad_accounts")
      .update({ last_synced_at: at.toISOString(), last_sync_error: null })
      .eq("id", account.id);
    if (error) throw error;
    const { error: auditError } = await this.db.from("audit_log").insert({
      organization_id: account.organizationId,
      action: "ad_account.synced",
      entity_type: "ad_account",
      entity_id: account.id,
      details: { ...summary },
    });
    if (auditError) throw auditError;
  }

  async markFailed(account: SyncAccount, error: string) {
    const { error: updateError } = await this.db
      .from("ad_accounts")
      .update({ last_sync_error: error.slice(0, 500) })
      .eq("id", account.id);
    if (updateError) throw updateError;
  }
}
