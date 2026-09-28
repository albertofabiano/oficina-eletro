import type { IsoDate } from "@/lib/dates";
import type { Cents } from "@/lib/money";
import type { CampaignStatus, PlatformId } from "./types";

/** A campaign as reported by the ad platform, identified by the platform's own id. */
export interface PlatformCampaign {
  externalId: string;
  name: string;
  status: CampaignStatus;
  dailyBudgetCents: Cents | null;
}

/** One campaign-day of metrics, in the account's calendar (America/Sao_Paulo). */
export interface PlatformInsight {
  campaignExternalId: string;
  date: IsoDate;
  spendCents: Cents;
  impressions: number;
  clicks: number;
  leads: number;
}

export interface AdAccountRef {
  externalId: string;
}

/**
 * Common contract for Meta Ads, Google Ads and the simulated platform.
 *
 * Read methods are used by the collector. Write methods must only be called by
 * the approval-queue executor, after a human approval is recorded and outside
 * DRY_RUN — never directly from UI code or optimization rules.
 */
export interface AdPlatform {
  readonly id: PlatformId;
  listCampaigns(account: AdAccountRef): Promise<PlatformCampaign[]>;
  getDailyInsights(account: AdAccountRef, range: { from: IsoDate; to: IsoDate }): Promise<PlatformInsight[]>;
  setCampaignStatus(account: AdAccountRef, campaignExternalId: string, status: "active" | "paused"): Promise<void>;
  setDailyBudget(account: AdAccountRef, campaignExternalId: string, dailyBudgetCents: Cents): Promise<void>;
}
