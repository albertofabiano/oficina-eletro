import { z } from "zod";
import type { ActionPayload, ActionType } from "./types";

const cents = z.number().int().positive();

/** Validates the stored jsonb payload before anything is sent to an ad platform. */
export function parseActionPayload(actionType: ActionType, payload: unknown): ActionPayload {
  switch (actionType) {
    case "pause_campaign":
      return { type: "pause_campaign" };
    case "resume_campaign":
      return { type: "resume_campaign" };
    case "update_daily_budget": {
      const parsed = z
        .object({ daily_budget_cents: cents, previous_daily_budget_cents: cents.nullable().optional() })
        .parse(payload);
      return {
        type: "update_daily_budget",
        toCents: parsed.daily_budget_cents,
        fromCents: parsed.previous_daily_budget_cents ?? parsed.daily_budget_cents,
      };
    }
  }
}

/** Database representation of an action payload. */
export function toStoredPayload(action: ActionPayload): Record<string, number> {
  return action.type === "update_daily_budget"
    ? { daily_budget_cents: action.toCents, previous_daily_budget_cents: action.fromCents }
    : {};
}
