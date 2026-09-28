import type { Campaign, DailyInsight } from "@/lib/ads/types";
import { eachDay, type IsoDate } from "@/lib/dates";
import type { DashboardDataSource } from "./data-source";
import type { DateRange } from "./period";

const CAMPAIGNS: Campaign[] = [
  { id: "cmp_tv", platform: "meta", name: "Conserto de TV — Leads", status: "active", dailyBudgetCents: 4_000 },
  { id: "cmp_cel", platform: "meta", name: "Troca de tela de celular", status: "active", dailyBudgetCents: 3_500 },
  { id: "cmp_gel", platform: "meta", name: "Geladeira — atendimento em domicílio", status: "active", dailyBudgetCents: 2_500 },
  { id: "cmp_rmk", platform: "meta", name: "Remarketing — site", status: "paused", dailyBudgetCents: 1_500 },
];

interface Profile {
  spendRatio: number;
  cpmCents: number;
  ctr: number;
  leadRate: number;
}

const PROFILES: Record<string, Profile> = {
  cmp_tv: { spendRatio: 0.95, cpmCents: 1_800, ctr: 0.014, leadRate: 0.09 },
  cmp_cel: { spendRatio: 0.9, cpmCents: 2_200, ctr: 0.018, leadRate: 0.04 },
  cmp_gel: { spendRatio: 0.85, cpmCents: 1_500, ctr: 0.009, leadRate: 0 },
  cmp_rmk: { spendRatio: 0, cpmCents: 3_000, ctr: 0.02, leadRate: 0.1 },
};

/** Deterministic value in [0, 1) so the fake data is stable between renders. */
function noise(seed: string): number {
  let hash = 2166136261;
  for (let i = 0; i < seed.length; i++) {
    hash ^= seed.charCodeAt(i);
    hash = Math.imul(hash, 16777619);
  }
  return (hash >>> 0) / 2 ** 32;
}

function fakeInsight(campaign: Campaign, date: IsoDate): DailyInsight {
  const profile = PROFILES[campaign.id];
  const budget = campaign.dailyBudgetCents ?? 0;
  if (!profile || profile.spendRatio === 0) {
    return { campaignId: campaign.id, date, spendCents: 0, impressions: 0, clicks: 0, leads: 0 };
  }
  const jitter = 0.8 + noise(`${campaign.id}:${date}`) * 0.4;
  const spendCents = Math.round(budget * profile.spendRatio * jitter);
  const impressions = Math.round((spendCents / profile.cpmCents) * 1000);
  const clicks = Math.round(impressions * profile.ctr * jitter);
  const leads = Math.floor(clicks * profile.leadRate * (0.5 + noise(`${date}:${campaign.id}`)));
  return { campaignId: campaign.id, date, spendCents, impressions, clicks, leads };
}

export class MockDashboardDataSource implements DashboardDataSource {
  async listCampaigns(): Promise<Campaign[]> {
    return CAMPAIGNS;
  }

  async getDailyInsights(range: DateRange): Promise<DailyInsight[]> {
    return eachDay(range.from, range.to).flatMap((date) =>
      CAMPAIGNS.map((campaign) => fakeInsight(campaign, date)),
    );
  }
}
