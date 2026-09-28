import { z } from "zod";
import type { IsoDate } from "@/lib/dates";
import { decimalToCents, type Cents } from "@/lib/money";
import type { AdAccountRef, AdPlatform, PlatformCampaign, PlatformInsight } from "../platform";
import { UsageThrottle } from "../rate-limit";
import type { CampaignStatus } from "../types";
import { MetaApiError, type GraphResponse, type MetaGraphClient } from "./graph-client";

/** Safety net against endless pagination. */
const MAX_PAGES = 200;
const PAGE_SIZE = "500";

/**
 * Lead actions, in order of preference. "lead" already aggregates every lead
 * source in current API versions; the others are only used when it is absent,
 * so the same lead is never counted twice.
 */
const LEAD_ACTION = "lead";
const LEAD_FALLBACK_ACTIONS = ["onsite_conversion.lead_grouped", "offsite_conversion.fb_pixel_lead"];

/** Graph error codes that mean the app, account or user hit a rate limit. */
const RATE_LIMIT_CODES = new Set([4, 17, 32, 613, 80000, 80001, 80002, 80003, 80004, 80005, 80006, 80008, 80009, 80014]);

const pageSchema = z.object({
  data: z.array(z.unknown()),
  paging: z.object({ next: z.string().optional() }).optional(),
});

const campaignSchema = z.object({
  id: z.string(),
  name: z.string(),
  status: z.string(),
  daily_budget: z.string().optional(),
});

const actionSchema = z.object({ action_type: z.string(), value: z.string() });
const insightSchema = z.object({
  campaign_id: z.string(),
  date_start: z.string().regex(/^\d{4}-\d{2}-\d{2}$/),
  spend: z.string().optional(),
  impressions: z.string().optional(),
  clicks: z.string().optional(),
  actions: z.array(actionSchema).optional(),
});

const accountSchema = z.object({
  id: z.string(),
  name: z.string(),
  currency: z.string(),
  timezone_name: z.string(),
  account_status: z.number(),
});

export interface MetaAccountInfo {
  externalId: string;
  name: string;
  currency: string;
  timezone: string;
  /** 1 active, 2 disabled, 3 unsettled, 7 pending risk review, 9 in grace period, 101 closed, … */
  accountStatus: number;
}

/** Accepts "123", "act_123" or pasted text with spaces; returns "act_123" or null. */
export function normalizeAdAccountId(input: string): string | null {
  const digits = input.trim().replace(/^act_/i, "");
  return /^\d{5,20}$/.test(digits) ? `act_${digits}` : null;
}

function mapStatus(status: string): CampaignStatus {
  switch (status) {
    case "ACTIVE":
      return "active";
    case "PAUSED":
      return "paused";
    default:
      return "archived"; // ARCHIVED, DELETED
  }
}

function toCount(value: string | undefined): number {
  if (value === undefined) return 0;
  if (!/^\d+$/.test(value)) throw new Error(`invalid count: ${value}`);
  return Number(value);
}

export function countLeads(actions: Array<{ action_type: string; value: string }> | undefined): number {
  if (!actions) return 0;
  const byType = new Map(actions.map((a) => [a.action_type, toCount(a.value)]));
  const lead = byType.get(LEAD_ACTION);
  if (lead !== undefined) return lead;
  return LEAD_FALLBACK_ACTIONS.reduce((sum, type) => sum + (byType.get(type) ?? 0), 0);
}

/** Portuguese message for the dashboard; Meta's own text stays in the logs of the caller. */
export function describeMetaError(error: MetaApiError): string {
  if (error.code === 190) return "Token de acesso da Meta inválido ou expirado. Gere um novo token e reconecte a conta.";
  // 10 and 200–299 are the permission errors.
  if (error.code === 10 || (error.code !== null && error.code >= 200 && error.code < 300)) {
    return "O token não tem permissão para esta conta de anúncios. Confira as permissões ads_read e ads_management.";
  }
  if (error.code === 100 && error.subcode === 33) return "Conta de anúncios não encontrada ou sem acesso para este token.";
  if (error.code !== null && RATE_LIMIT_CODES.has(error.code)) {
    return "Limite de uso da API da Meta atingido. A coleta será refeita na próxima sincronização.";
  }
  // Without a Graph error code the message is already ours (e.g. network failure).
  return error.code === null ? error.message : `Erro da API da Meta: ${error.message}`;
}

/**
 * Meta Ads through the Graph API. Every request waits according to the last
 * `x-business-use-case-usage` header, slowing down above 75% usage.
 */
export class MetaAdsPlatform implements AdPlatform {
  readonly id = "meta" as const;

  constructor(
    private readonly client: MetaGraphClient,
    private readonly throttle = new UsageThrottle(),
  ) {}

  async getAccountInfo(externalId: string): Promise<MetaAccountInfo> {
    const { body } = await this.request(() =>
      this.client.get(externalId, { fields: "id,name,currency,timezone_name,account_status" }),
    );
    const account = accountSchema.parse(body);
    return {
      externalId: account.id,
      name: account.name,
      currency: account.currency,
      timezone: account.timezone_name,
      accountStatus: account.account_status,
    };
  }

  async listCampaigns(account: AdAccountRef): Promise<PlatformCampaign[]> {
    const rows = await this.paginate(`${account.externalId}/campaigns`, {
      fields: "id,name,status,daily_budget",
      // Without this filter the edge omits archived and deleted campaigns, and
      // they would stay "active" in our database forever.
      effective_status: JSON.stringify(["ACTIVE", "PAUSED", "ARCHIVED", "DELETED", "IN_PROCESS", "WITH_ISSUES"]),
      limit: PAGE_SIZE,
    });
    return rows.map((row) => {
      const campaign = campaignSchema.parse(row);
      return {
        externalId: campaign.id,
        name: campaign.name,
        status: mapStatus(campaign.status),
        // Minor units of the account currency (BRL → cents). Absent when the budget lives on the ad sets.
        dailyBudgetCents: campaign.daily_budget ? toCount(campaign.daily_budget) : null,
      };
    });
  }

  async getDailyInsights(account: AdAccountRef, range: { from: IsoDate; to: IsoDate }): Promise<PlatformInsight[]> {
    const rows = await this.paginate(`${account.externalId}/insights`, {
      level: "campaign",
      time_increment: "1",
      time_range: JSON.stringify({ since: range.from, until: range.to }),
      fields: "campaign_id,date_start,spend,impressions,clicks,actions",
      limit: PAGE_SIZE,
    });
    return rows.map((row) => {
      const insight = insightSchema.parse(row);
      return {
        campaignExternalId: insight.campaign_id,
        // Insights come in the account's timezone, which is required to be America/Sao_Paulo.
        date: insight.date_start as IsoDate,
        spendCents: insight.spend ? decimalToCents(insight.spend) : 0,
        impressions: toCount(insight.impressions),
        clicks: toCount(insight.clicks),
        leads: countLeads(insight.actions),
      };
    });
  }

  async setCampaignStatus(_account: AdAccountRef, campaignExternalId: string, status: "active" | "paused") {
    await this.request(() => this.client.post(campaignExternalId, { status: status === "active" ? "ACTIVE" : "PAUSED" }));
  }

  async setDailyBudget(_account: AdAccountRef, campaignExternalId: string, dailyBudgetCents: Cents) {
    if (!Number.isInteger(dailyBudgetCents) || dailyBudgetCents <= 0) throw new Error("invalid budget");
    await this.request(() => this.client.post(campaignExternalId, { daily_budget: String(dailyBudgetCents) }));
  }

  private async paginate(path: string, params: Record<string, string>): Promise<unknown[]> {
    const rows: unknown[] = [];
    let response = await this.request(() => this.client.get(path, params));
    for (let page = 1; ; page++) {
      const { data, paging } = pageSchema.parse(response.body);
      rows.push(...data);
      if (!paging?.next) return rows;
      if (page >= MAX_PAGES) throw new Error(`Paginação da Meta excedeu ${MAX_PAGES} páginas em ${path}.`);
      const next = paging.next;
      response = await this.request(() => this.client.getNext(next));
    }
  }

  private async request(send: () => Promise<GraphResponse>): Promise<GraphResponse> {
    await this.throttle.beforeRequest();
    try {
      const response = await send();
      this.throttle.record(response.usageHeader);
      return response;
    } catch (error) {
      if (error instanceof MetaApiError) {
        if (error.usageHeader) this.throttle.record(error.usageHeader);
        throw new MetaApiError(describeMetaError(error), error.code, error.subcode, error.usageHeader);
      }
      throw error;
    }
  }
}
