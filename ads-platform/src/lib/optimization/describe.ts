import { formatCents } from "@/lib/money";
import type { ActionPayload } from "./types";

/** Human-readable description of an action, for the approval screen. */
export function describeAction(action: ActionPayload): string {
  switch (action.type) {
    case "pause_campaign":
      return "Pausar campanha";
    case "resume_campaign":
      return "Reativar campanha";
    case "update_daily_budget":
      return `${action.toCents > action.fromCents ? "Aumentar" : "Reduzir"} orçamento diário de ${formatCents(action.fromCents)} para ${formatCents(action.toCents)}`;
  }
}
