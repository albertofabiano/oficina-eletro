import type { SupabaseClient } from "@supabase/supabase-js";
import type { Campaign, DailyInsight } from "@/lib/ads/types";
import type { Database } from "@/lib/supabase/database.types";
import type { DashboardDataSource } from "./data-source";
import type { DateRange } from "./period";

const PAGE = 1000;

/** Reads the dashboard data as the signed-in user; RLS limits it to their organization. */
export class SupabaseDashboardDataSource implements DashboardDataSource {
  constructor(
    private readonly db: SupabaseClient<Database>,
    private readonly organizationId: string,
  ) {}

  async listCampaigns(): Promise<Campaign[]> {
    const [campaigns, accounts] = await Promise.all([
      this.db
        .from("campaigns")
        .select("id, name, status, daily_budget_cents, ad_account_id")
        .eq("organization_id", this.organizationId),
      this.db.from("ad_accounts").select("id, platform").eq("organization_id", this.organizationId),
    ]);
    if (campaigns.error) throw campaigns.error;
    if (accounts.error) throw accounts.error;
    const platformByAccount = new Map(accounts.data.map((a) => [a.id, a.platform]));
    return campaigns.data.map((c) => ({
      id: c.id,
      name: c.name,
      status: c.status,
      dailyBudgetCents: c.daily_budget_cents,
      platform: platformByAccount.get(c.ad_account_id) ?? "meta",
    }));
  }

  async getDailyInsights(range: DateRange): Promise<DailyInsight[]> {
    const rows: DailyInsight[] = [];
    // PostgREST caps responses, so page through the range.
    for (let offset = 0; ; offset += PAGE) {
      const { data, error } = await this.db
        .from("daily_insights")
        .select("campaign_id, date, spend_cents, impressions, clicks, leads")
        .eq("organization_id", this.organizationId)
        .gte("date", range.from)
        .lte("date", range.to)
        .order("date")
        .order("campaign_id")
        .range(offset, offset + PAGE - 1);
      if (error) throw error;
      for (const r of data) {
        rows.push({
          campaignId: r.campaign_id,
          date: r.date,
          spendCents: r.spend_cents,
          impressions: r.impressions,
          clicks: r.clicks,
          leads: r.leads,
        });
      }
      if (data.length < PAGE) return rows;
    }
  }
}

export interface AdAccountOverview {
  id: string;
  name: string;
  platform: "meta" | "fake";
  lastSyncedAt: string | null;
}

export async function listAdAccounts(db: SupabaseClient<Database>, organizationId: string): Promise<AdAccountOverview[]> {
  const { data, error } = await db
    .from("ad_accounts")
    .select("id, name, platform, last_synced_at")
    .eq("organization_id", organizationId)
    .order("created_at");
  if (error) throw error;
  return data.map((a) => ({ id: a.id, name: a.name, platform: a.platform, lastSyncedAt: a.last_synced_at }));
}
