import type { PlatformId } from "@/lib/ads/types";
import type { ActionType, Suggestion } from "@/lib/optimization/types";

export interface ApprovedRequest {
  id: string;
  organizationId: string;
  campaignId: string;
  actionType: ActionType;
  payload: unknown;
}

export interface ExecutionTarget {
  platform: PlatformId;
  accountExternalId: string;
  campaignExternalId: string;
}

/** Persistence for the approval queue; Supabase in production, in-memory in tests. */
export interface QueueStore {
  listOrganizationsWithAccounts(): Promise<string[]>;
  /** Keys "campaignId:actionType" that must not get a new suggestion (pending, or rejected since `since`). */
  listBlockedKeys(organizationId: string, since: Date): Promise<Set<string>>;
  insertSuggestions(organizationId: string, suggestions: Suggestion[]): Promise<void>;

  /** Only requests with status "approved" — never pending or rejected ones. */
  listApproved(filter: { organizationId?: string; requestId?: string }): Promise<ApprovedRequest[]>;
  loadTarget(campaignId: string): Promise<ExecutionTarget | null>;
  /** Mirrors a change that really reached the platform into the local campaign row. */
  applyToCampaign(campaignId: string, change: { status?: "active" | "paused"; dailyBudgetCents?: number }): Promise<void>;
  markExecuted(request: ApprovedRequest, result: { dryRun: boolean; at: Date }): Promise<void>;
  markFailed(request: ApprovedRequest, error: string, at: Date): Promise<void>;
}

export const blockKey = (campaignId: string, actionType: ActionType) => `${campaignId}:${actionType}`;
