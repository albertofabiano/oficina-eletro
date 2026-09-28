import type { Suggestion } from "@/lib/optimization/types";
import { toStoredPayload } from "@/lib/optimization/payload";
import { blockKey, type ApprovedRequest, type ExecutionTarget, type QueueStore } from "./store";

type Status = "pending" | "approved" | "rejected" | "executed" | "failed";

export interface MemoryRequest extends ApprovedRequest {
  status: Status;
  reason: string;
  ruleId: string;
  decidedAt?: Date;
  executedAt?: Date;
  dryRun?: boolean;
  error?: string;
}

/** In-memory QueueStore for tests. */
export class MemoryQueueStore implements QueueStore {
  requests: MemoryRequest[] = [];
  targets = new Map<string, ExecutionTarget>();
  campaignChanges: Array<{ campaignId: string; change: object }> = [];
  organizations: string[] = [];
  private nextId = 1;

  async listOrganizationsWithAccounts() {
    return this.organizations;
  }

  async listBlockedKeys(organizationId: string, since: Date) {
    return new Set(
      this.requests
        .filter(
          (r) =>
            r.organizationId === organizationId &&
            (r.status === "pending" || (r.status === "rejected" && (r.decidedAt ?? new Date(0)) >= since)),
        )
        .map((r) => blockKey(r.campaignId, r.actionType)),
    );
  }

  async insertSuggestions(organizationId: string, suggestions: Suggestion[]) {
    for (const s of suggestions) {
      this.requests.push({
        id: `r${this.nextId++}`,
        organizationId,
        campaignId: s.campaignId,
        actionType: s.action.type,
        payload: toStoredPayload(s.action),
        status: "pending",
        reason: s.reason,
        ruleId: s.ruleId,
      });
    }
  }

  async listApproved(filter: { organizationId?: string; requestId?: string }) {
    return this.requests.filter(
      (r) =>
        r.status === "approved" &&
        (!filter.organizationId || r.organizationId === filter.organizationId) &&
        (!filter.requestId || r.id === filter.requestId),
    );
  }

  async loadTarget(campaignId: string) {
    return this.targets.get(campaignId) ?? null;
  }

  async applyToCampaign(campaignId: string, change: object) {
    this.campaignChanges.push({ campaignId, change });
  }

  async markExecuted(request: ApprovedRequest, result: { dryRun: boolean; at: Date }) {
    Object.assign(this.find(request.id), { status: "executed", dryRun: result.dryRun, executedAt: result.at });
  }

  async markFailed(request: ApprovedRequest, error: string, at: Date) {
    Object.assign(this.find(request.id), { status: "failed", error, executedAt: at });
  }

  private find(id: string) {
    const request = this.requests.find((r) => r.id === id);
    if (!request) throw new Error(`request ${id} not found`);
    return request;
  }
}
