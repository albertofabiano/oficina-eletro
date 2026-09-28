import { describe, expect, it } from "vitest";
import { UsageThrottle } from "../rate-limit";
import { MetaApiError, type GraphResponse, type MetaGraphClient } from "./graph-client";
import { countLeads, MetaAdsPlatform, normalizeAdAccountId } from "./meta-platform";

type Call = { method: "get" | "post" | "next"; path: string; params?: Record<string, string> };

/** Graph client fake: answers from a list of canned responses, in order, and records the calls. */
function fakeClient(responses: Array<GraphResponse | MetaApiError>) {
  const calls: Call[] = [];
  const reply = (call: Call) => {
    calls.push(call);
    const next = responses.shift();
    if (!next) throw new Error(`unexpected call ${call.method} ${call.path}`);
    if (next instanceof MetaApiError) return Promise.reject(next);
    return Promise.resolve(next);
  };
  const client: MetaGraphClient = {
    get: (path, params) => reply({ method: "get", path, params }),
    post: (path, params) => reply({ method: "post", path, params }),
    getNext: (url) => reply({ method: "next", path: url }),
  };
  return { client, calls };
}

const ok = (body: unknown, usageHeader: string | null = null): GraphResponse => ({ body, usageHeader });
const usage = (percent: number, regain = 0) =>
  JSON.stringify({ "999": [{ type: "ads_management", call_count: percent, total_cputime: 1, total_time: 1, estimated_time_to_regain_access: regain }] });
const account = { externalId: "act_123456789" };

function recordingThrottle() {
  const sleeps: number[] = [];
  const throttle = new UsageThrottle(async (ms) => {
    sleeps.push(ms);
  });
  return { throttle, sleeps };
}

describe("normalizeAdAccountId", () => {
  it("accepts the id with or without the act_ prefix", () => {
    expect(normalizeAdAccountId("123456789")).toBe("act_123456789");
    expect(normalizeAdAccountId(" act_123456789 ")).toBe("act_123456789");
    expect(normalizeAdAccountId("ACT_123456789")).toBe("act_123456789");
  });

  it("rejects anything that is not a numeric id", () => {
    expect(normalizeAdAccountId("")).toBeNull();
    expect(normalizeAdAccountId("act_12ab")).toBeNull();
    expect(normalizeAdAccountId("123/../me")).toBeNull();
  });
});

describe("countLeads", () => {
  it("prefers the aggregated lead action so leads are not double counted", () => {
    expect(
      countLeads([
        { action_type: "lead", value: "5" },
        { action_type: "onsite_conversion.lead_grouped", value: "3" },
        { action_type: "offsite_conversion.fb_pixel_lead", value: "2" },
      ]),
    ).toBe(5);
  });

  it("falls back to form and pixel leads", () => {
    expect(
      countLeads([
        { action_type: "link_click", value: "40" },
        { action_type: "onsite_conversion.lead_grouped", value: "3" },
        { action_type: "offsite_conversion.fb_pixel_lead", value: "2" },
      ]),
    ).toBe(5);
    expect(countLeads(undefined)).toBe(0);
  });
});

describe("MetaAdsPlatform.listCampaigns", () => {
  it("maps statuses and budgets in cents, following pagination", async () => {
    const { client, calls } = fakeClient([
      ok({
        data: [
          { id: "1", name: "Conserto de TV", status: "ACTIVE", daily_budget: "4000" },
          { id: "2", name: "Celular (orçamento no conjunto)", status: "PAUSED" },
        ],
        paging: { next: "https://graph.facebook.com/v24.0/act_123456789/campaigns?after=abc" },
      }),
      ok({ data: [{ id: "3", name: "Antiga", status: "ARCHIVED", daily_budget: "1500" }, { id: "4", name: "Apagada", status: "DELETED" }] }),
    ]);
    const platform = new MetaAdsPlatform(client, recordingThrottle().throttle);

    expect(await platform.listCampaigns(account)).toEqual([
      { externalId: "1", name: "Conserto de TV", status: "active", dailyBudgetCents: 4000 },
      { externalId: "2", name: "Celular (orçamento no conjunto)", status: "paused", dailyBudgetCents: null },
      { externalId: "3", name: "Antiga", status: "archived", dailyBudgetCents: 1500 },
      { externalId: "4", name: "Apagada", status: "archived", dailyBudgetCents: null },
    ]);
    expect(calls[0]).toMatchObject({ method: "get", path: "act_123456789/campaigns" });
    expect(JSON.parse(calls[0]!.params!.effective_status!)).toContain("ARCHIVED");
    expect(calls[1]).toMatchObject({ method: "next" });
  });

  it("rejects malformed responses instead of storing bad data", async () => {
    const { client } = fakeClient([ok({ data: [{ id: "1", name: "X", status: "ACTIVE", daily_budget: "40.5" }] })]);
    await expect(new MetaAdsPlatform(client, recordingThrottle().throttle).listCampaigns(account)).rejects.toThrow();
  });
});

describe("MetaAdsPlatform.getDailyInsights", () => {
  it("requests daily campaign rows and converts money to integer cents", async () => {
    const { client, calls } = fakeClient([
      ok({
        data: [
          {
            campaign_id: "1",
            date_start: "2026-09-26",
            date_stop: "2026-09-26",
            spend: "37.89",
            impressions: "2104",
            clicks: "31",
            actions: [
              { action_type: "link_click", value: "31" },
              { action_type: "lead", value: "3" },
            ],
          },
          { campaign_id: "2", date_start: "2026-09-26", date_stop: "2026-09-26", spend: "0.105", impressions: "12" },
        ],
      }),
    ]);
    const platform = new MetaAdsPlatform(client, recordingThrottle().throttle);

    expect(await platform.getDailyInsights(account, { from: "2026-09-20", to: "2026-09-26" })).toEqual([
      { campaignExternalId: "1", date: "2026-09-26", spendCents: 3789, impressions: 2104, clicks: 31, leads: 3 },
      { campaignExternalId: "2", date: "2026-09-26", spendCents: 11, impressions: 12, clicks: 0, leads: 0 },
    ]);
    expect(calls[0]!.path).toBe("act_123456789/insights");
    expect(calls[0]!.params).toMatchObject({ level: "campaign", time_increment: "1" });
    expect(JSON.parse(calls[0]!.params!.time_range!)).toEqual({ since: "2026-09-20", until: "2026-09-26" });
  });
});

describe("MetaAdsPlatform writes", () => {
  it("sends status and budget in Meta's format", async () => {
    const { client, calls } = fakeClient([ok({ success: true }), ok({ success: true }), ok({ success: true })]);
    const platform = new MetaAdsPlatform(client, recordingThrottle().throttle);

    await platform.setCampaignStatus(account, "1", "paused");
    await platform.setCampaignStatus(account, "1", "active");
    await platform.setDailyBudget(account, "1", 3200);

    expect(calls).toEqual([
      { method: "post", path: "1", params: { status: "PAUSED" } },
      { method: "post", path: "1", params: { status: "ACTIVE" } },
      { method: "post", path: "1", params: { daily_budget: "3200" } },
    ]);
  });

  it("refuses non-integer budgets before calling Meta", async () => {
    const { client, calls } = fakeClient([]);
    await expect(new MetaAdsPlatform(client).setDailyBudget(account, "1", 32.5)).rejects.toThrow("invalid budget");
    expect(calls).toHaveLength(0);
  });
});

describe("MetaAdsPlatform rate limiting", () => {
  it("slows down only after usage goes above 75%", async () => {
    const { client } = fakeClient([
      ok({ data: [], paging: { next: "https://graph.facebook.com/v24.0/next1" } }, usage(50)),
      ok({ data: [], paging: { next: "https://graph.facebook.com/v24.0/next2" } }, usage(90)),
      ok({ data: [] }, usage(100)),
    ]);
    const { throttle, sleeps } = recordingThrottle();
    await new MetaAdsPlatform(client, throttle).listCampaigns(account);
    // No wait after 50%; 90% → 60% of the maximum delay before the last page.
    expect(sleeps).toEqual([36_000]);
  });

  it("waits the time Meta asks for when blocked, even from an error response", async () => {
    const { client } = fakeClient([
      new MetaApiError("User request limit reached", 17, 2446079, usage(100, 2)),
      ok({ success: true }),
    ]);
    const { throttle, sleeps } = recordingThrottle();
    const platform = new MetaAdsPlatform(client, throttle);
    await expect(platform.setCampaignStatus(account, "1", "paused")).rejects.toThrow("Limite de uso da API da Meta");
    await platform.setCampaignStatus(account, "1", "paused");
    expect(sleeps).toEqual([120_000]);
  });
});

describe("MetaAdsPlatform errors", () => {
  it("translates common Graph errors to Portuguese", async () => {
    const cases: Array<[MetaApiError, string]> = [
      [new MetaApiError("Error validating access token", 190, 463), "Token de acesso da Meta inválido ou expirado"],
      [new MetaApiError("Permissions error", 200, null), "não tem permissão"],
      [new MetaApiError("Unsupported get request", 100, 33), "Conta de anúncios não encontrada"],
    ];
    for (const [error, message] of cases) {
      const { client } = fakeClient([error]);
      await expect(new MetaAdsPlatform(client, recordingThrottle().throttle).getAccountInfo("act_1")).rejects.toThrow(message);
    }
  });

  it("reads the account details used to validate a connection", async () => {
    const { client, calls } = fakeClient([
      ok({ id: "act_123456789", name: "Oficina", currency: "BRL", timezone_name: "America/Sao_Paulo", account_status: 1 }),
    ]);
    expect(await new MetaAdsPlatform(client, recordingThrottle().throttle).getAccountInfo("act_123456789")).toEqual({
      externalId: "act_123456789",
      name: "Oficina",
      currency: "BRL",
      timezone: "America/Sao_Paulo",
      accountStatus: 1,
    });
    expect(calls[0]!.params!.fields).toContain("timezone_name");
  });
});
