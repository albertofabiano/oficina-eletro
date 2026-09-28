import type { IsoDate } from "@/lib/dates";
import type { Cents } from "@/lib/money";

/** "fake" is the simulated platform used until real Meta credentials exist. */
export type PlatformId = "meta" | "fake";

export type CampaignStatus = "active" | "paused" | "archived";

export interface Campaign {
  id: string;
  platform: PlatformId;
  name: string;
  status: CampaignStatus;
  dailyBudgetCents: Cents | null;
}

export interface DailyInsight {
  campaignId: string;
  date: IsoDate;
  spendCents: Cents;
  impressions: number;
  clicks: number;
  leads: number;
}
