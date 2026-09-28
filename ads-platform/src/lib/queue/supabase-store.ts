import { toStoredPayload } from "@/lib/optimization/payload";
import type { Suggestion } from "@/lib/optimization/types";
import type { AdminClient } from "@/lib/supabase/admin";
import { blockKey, type ApprovedRequest, type ExecutionTarget, type QueueStore } from "./store";

const UNIQUE_VIOLATION = "23505";

/** QueueStore backed by Supabase through the service-role client (server only). */
export class SupabaseQueueStore implements QueueStore {
  constructor(private readonly db: AdminClient) {}

  async listOrganizationsWithAccounts() {
    const { data, error } = await this.db.from("ad_accounts").select("organization_id").eq("status", "active");
    if (error) throw error;
    return [...new Set(data.map((row) => row.organization_id))];
  }

  async listBlockedKeys(organizationId: string, since: Date) {
    const { data, error } = await this.db
      .from("action_requests")
      .select("campaign_id, action_type")
      .eq("organization_id", organizationId)
      .or(`status.eq.pending,and(status.eq.rejected,decided_at.gte.${since.toISOString()})`);
    if (error) throw error;
    return new Set(data.map((row) => blockKey(row.campaign_id, row.action_type)));
  }

  async insertSuggestions(organizationId: string, suggestions: Suggestion[]) {
    for (const s of suggestions) {
      const { error } = await this.db.from("action_requests").insert({
        organization_id: organizationId,
        campaign_id: s.campaignId,
        action_type: s.action.type,
        payload: toStoredPayload(s.action),
        reason: s.reason,
        source: "rule",
        rule_id: s.ruleId,
      });
      // A concurrent run may have queued the same suggestion; the unique index keeps one.
      if (error && error.code !== UNIQUE_VIOLATION) throw error;
    }
  }

  async listApproved(filter: { organizationId?: string; requestId?: string }): Promise<ApprovedRequest[]> {
    let query = this.db
      .from("action_requests")
      .select("id, organization_id, campaign_id, action_type, payload")
      .eq("status", "approved")
      .order("decided_at");
    if (filter.organizationId) query = query.eq("organization_id", filter.organizationId);
    if (filter.requestId) query = query.eq("id", filter.requestId);
    const { data, error } = await query;
    if (error) throw error;
    return data.map((row) => ({
      id: row.id,
      organizationId: row.organization_id,
      campaignId: row.campaign_id,
      actionType: row.action_type,
      payload: row.payload,
    }));
  }

  async loadTarget(campaignId: string): Promise<ExecutionTarget | null> {
    const { data: campaign, error } = await this.db
      .from("campaigns")
      .select("external_id, ad_account_id")
      .eq("id", campaignId)
      .maybeSingle();
    if (error) throw error;
    if (!campaign) return null;
    const { data: account, error: accountError } = await this.db
      .from("ad_accounts")
      .select("platform, external_id")
      .eq("id", campaign.ad_account_id)
      .maybeSingle();
    if (accountError) throw accountError;
    if (!account) return null;
    return { platform: account.platform, accountExternalId: account.external_id, campaignExternalId: campaign.external_id };
  }

  async applyToCampaign(campaignId: string, change: { status?: "active" | "paused"; dailyBudgetCents?: number }) {
    const { error } = await this.db
      .from("campaigns")
      .update({
        ...(change.status ? { status: change.status } : {}),
        ...(change.dailyBudgetCents !== undefined ? { daily_budget_cents: change.dailyBudgetCents } : {}),
      })
      .eq("id", campaignId);
    if (error) throw error;
  }

  async markExecuted(request: ApprovedRequest, result: { dryRun: boolean; at: Date }) {
    await this.finish(request, { status: "executed", executed_at: result.at.toISOString(), dry_run: result.dryRun });
    await this.audit(request, "action_request.executed", { dry_run: result.dryRun });
  }

  async markFailed(request: ApprovedRequest, error: string, at: Date) {
    await this.finish(request, { status: "failed", executed_at: at.toISOString(), error: error.slice(0, 500) });
    await this.audit(request, "action_request.failed", { error: error.slice(0, 500) });
  }

  private async finish(
    request: ApprovedRequest,
    changes: { status: "executed" | "failed"; executed_at: string; dry_run?: boolean; error?: string },
  ) {
    const { error } = await this.db.from("action_requests").update(changes).eq("id", request.id).eq("status", "approved");
    if (error) throw error;
  }

  private async audit(request: ApprovedRequest, action: string, details: Record<string, unknown>) {
    const { error } = await this.db.from("audit_log").insert({
      organization_id: request.organizationId,
      action,
      entity_type: "action_request",
      entity_id: request.id,
      details: { action_type: request.actionType, ...details },
    });
    if (error) throw error;
  }
}
