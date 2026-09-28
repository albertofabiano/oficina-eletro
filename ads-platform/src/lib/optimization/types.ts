import type { Cents } from "@/lib/money";

export type ActionType = "pause_campaign" | "resume_campaign" | "update_daily_budget";

export type ActionPayload =
  | { type: "pause_campaign" }
  | { type: "resume_campaign" }
  | { type: "update_daily_budget"; fromCents: Cents; toCents: Cents };

/** A proposed change. It only becomes an action after a human approves it. */
export interface Suggestion {
  campaignId: string;
  ruleId: string;
  action: ActionPayload;
  reason: string;
}
