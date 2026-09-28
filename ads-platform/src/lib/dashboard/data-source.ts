import type { Campaign, DailyInsight } from "@/lib/ads/types";
import type { DateRange } from "./period";

/** Read side of the dashboard. The mock is replaced by a Supabase implementation later. */
export interface DashboardDataSource {
  listCampaigns(): Promise<Campaign[]>;
  getDailyInsights(range: DateRange): Promise<DailyInsight[]>;
}
