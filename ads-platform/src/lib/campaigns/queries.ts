import type { SupabaseClient } from "@supabase/supabase-js";
import type { CampaignStatus, DailyInsight, PlatformId } from "@/lib/ads/types";
import type { DateRange } from "@/lib/dashboard/period";
import type { Database } from "@/lib/supabase/database.types";

export interface CampaignOverview {
  id: string;
  name: string;
  status: CampaignStatus;
  dailyBudgetCents: number | null;
  syncedAt: string;
  accountName: string;
  platform: PlatformId;
}

/** One campaign of the user's organization, or null (RLS hides other organizations). */
export async function getCampaign(
  db: SupabaseClient<Database>,
  organizationId: string,
  campaignId: string,
): Promise<CampaignOverview | null> {
  const { data: campaign, error } = await db
    .from("campaigns")
    .select("id, name, status, daily_budget_cents, synced_at, ad_account_id")
    .eq("organization_id", organizationId)
    .eq("id", campaignId)
    .maybeSingle();
  if (error) throw error;
  if (!campaign) return null;

  const { data: account, error: accountError } = await db
    .from("ad_accounts")
    .select("name, platform")
    .eq("id", campaign.ad_account_id)
    .maybeSingle();
  if (accountError) throw accountError;

  return {
    id: campaign.id,
    name: campaign.name,
    status: campaign.status,
    dailyBudgetCents: campaign.daily_budget_cents,
    syncedAt: campaign.synced_at,
    accountName: account?.name ?? "—",
    platform: account?.platform ?? "meta",
  };
}

export async function getCampaignInsights(
  db: SupabaseClient<Database>,
  organizationId: string,
  campaignId: string,
  range: DateRange,
): Promise<DailyInsight[]> {
  const { data, error } = await db
    .from("daily_insights")
    .select("campaign_id, date, spend_cents, impressions, clicks, leads")
    .eq("organization_id", organizationId)
    .eq("campaign_id", campaignId)
    .gte("date", range.from)
    .lte("date", range.to)
    .order("date");
  if (error) throw error;
  return data.map((r) => ({
    campaignId: r.campaign_id,
    date: r.date,
    spendCents: r.spend_cents,
    impressions: r.impressions,
    clicks: r.clicks,
    leads: r.leads,
  }));
}
