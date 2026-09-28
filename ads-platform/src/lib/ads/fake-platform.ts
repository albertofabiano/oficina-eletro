import { eachDay, type IsoDate } from "@/lib/dates";
import type { Cents } from "@/lib/money";
import type { AdAccountRef, AdPlatform, PlatformCampaign, PlatformInsight } from "./platform";
import { UsageThrottle } from "./rate-limit";
import type { CampaignStatus } from "./types";

interface Profile {
  name: string;
  status: CampaignStatus;
  dailyBudgetCents: Cents;
  spendRatio: number;
  cpmCents: number;
  ctr: number;
  leadRate: number;
}

/** Service-business campaigns with distinct behaviors so every alert rule has something to find. */
const PROFILES: Record<string, Profile> = {
  tv: { name: "Conserto de TV — Leads", status: "active", dailyBudgetCents: 4_000, spendRatio: 0.95, cpmCents: 1_800, ctr: 0.014, leadRate: 0.09 },
  cel: { name: "Troca de tela de celular", status: "active", dailyBudgetCents: 3_500, spendRatio: 0.9, cpmCents: 2_200, ctr: 0.018, leadRate: 0.04 },
  gel: { name: "Geladeira — atendimento em domicílio", status: "active", dailyBudgetCents: 2_500, spendRatio: 0.85, cpmCents: 1_500, ctr: 0.009, leadRate: 0 },
  rmk: { name: "Remarketing — site", status: "paused", dailyBudgetCents: 1_500, spendRatio: 0.9, cpmCents: 3_000, ctr: 0.02, leadRate: 0.1 },
};

/** Deterministic value in [0, 1) so the same account/day always yields the same numbers. */
function noise(seed: string): number {
  let hash = 2166136261;
  for (let i = 0; i < seed.length; i++) {
    hash ^= seed.charCodeAt(i);
    hash = Math.imul(hash, 16777619);
  }
  return (hash >>> 0) / 2 ** 32;
}

/** Changes made through the write methods, kept per process like a remote platform would. */
const overrides = new Map<string, { status?: CampaignStatus; dailyBudgetCents?: Cents }>();

/**
 * Simulated Meta Ads used until real credentials exist. Behaves like the real
 * API from the caller's point of view, including the usage header that drives
 * the rate-limit throttle.
 */
export class FakeAdPlatform implements AdPlatform {
  readonly id = "fake" as const;
  private calls = 0;

  constructor(
    private readonly throttle = new UsageThrottle(),
    /** Simulated usage percentage added per API call. */
    private readonly usagePerCall = 2,
  ) {}

  async listCampaigns(account: AdAccountRef): Promise<PlatformCampaign[]> {
    await this.request();
    return Object.keys(PROFILES).map((key) => this.campaign(account, key));
  }

  async getDailyInsights(account: AdAccountRef, range: { from: IsoDate; to: IsoDate }): Promise<PlatformInsight[]> {
    await this.request();
    const campaigns = Object.keys(PROFILES).map((key) => ({ key, campaign: this.campaign(account, key) }));
    return eachDay(range.from, range.to).flatMap((date) =>
      campaigns.map(({ key, campaign }) => this.insight(account, key, campaign, date)),
    );
  }

  async setCampaignStatus(account: AdAccountRef, campaignExternalId: string, status: "active" | "paused") {
    await this.request();
    this.override(account, campaignExternalId, { status });
  }

  async setDailyBudget(account: AdAccountRef, campaignExternalId: string, dailyBudgetCents: Cents) {
    await this.request();
    if (!Number.isInteger(dailyBudgetCents) || dailyBudgetCents <= 0) throw new Error("invalid budget");
    this.override(account, campaignExternalId, { dailyBudgetCents });
  }

  private async request() {
    await this.throttle.beforeRequest();
    this.calls += 1;
    const usage = Math.min(100, this.calls * this.usagePerCall);
    this.throttle.record(JSON.stringify({ fake: [{ type: "ads_insights", call_count: usage, total_cputime: usage / 2, total_time: usage / 2 }] }));
  }

  private externalId(account: AdAccountRef, key: string) {
    return `${account.externalId}_${key}`;
  }

  private override(account: AdAccountRef, campaignExternalId: string, change: { status?: CampaignStatus; dailyBudgetCents?: Cents }) {
    const key = Object.keys(PROFILES).find((k) => this.externalId(account, k) === campaignExternalId);
    if (!key) throw new Error(`campaign not found: ${campaignExternalId}`);
    overrides.set(campaignExternalId, { ...overrides.get(campaignExternalId), ...change });
  }

  private campaign(account: AdAccountRef, key: string): PlatformCampaign {
    const profile = PROFILES[key]!;
    const externalId = this.externalId(account, key);
    const override = overrides.get(externalId);
    return {
      externalId,
      name: profile.name,
      status: override?.status ?? profile.status,
      dailyBudgetCents: override?.dailyBudgetCents ?? profile.dailyBudgetCents,
    };
  }

  private insight(account: AdAccountRef, key: string, campaign: PlatformCampaign, date: IsoDate): PlatformInsight {
    const profile = PROFILES[key]!;
    const empty = { campaignExternalId: campaign.externalId, date, spendCents: 0, impressions: 0, clicks: 0, leads: 0 };
    if (campaign.status !== "active") return empty;

    const seed = `${account.externalId}:${key}:${date}`;
    const jitter = 0.8 + noise(seed) * 0.4;
    const spendCents = Math.round((campaign.dailyBudgetCents ?? 0) * profile.spendRatio * jitter);
    const impressions = Math.round((spendCents / profile.cpmCents) * 1000);
    const clicks = Math.round(impressions * profile.ctr * jitter);
    const leads = Math.floor(clicks * profile.leadRate * (0.5 + noise(`${seed}:leads`)));
    return { ...empty, spendCents, impressions, clicks, leads };
  }
}

/** Test helper: forget simulated writes between test cases. */
export function resetFakePlatformState() {
  overrides.clear();
}
