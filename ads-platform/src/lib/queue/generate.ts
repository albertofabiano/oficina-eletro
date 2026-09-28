import { addDays, todayInSaoPaulo } from "@/lib/dates";
import type { DashboardDataSource } from "@/lib/dashboard/data-source";
import { campaignRows, deriveMetrics, sumInsights } from "@/lib/dashboard/metrics";
import { suggestOptimizations } from "@/lib/optimization/rules";
import { blockKey, type QueueStore } from "./store";

export const SUGGESTION_WINDOW_DAYS = 7;
/** A rejected suggestion is not proposed again for this long. */
export const REJECTION_COOLDOWN_DAYS = 7;

/**
 * Evaluates the optimization rules over the last complete week and queues new
 * suggestions as pending action requests. Nothing is executed here.
 */
export async function generateSuggestions(options: {
  source: DashboardDataSource;
  queue: QueueStore;
  organizationId: string;
  now?: Date;
}): Promise<number> {
  const now = options.now ?? new Date();
  const to = addDays(todayInSaoPaulo(now), -1);
  const from = addDays(to, -(SUGGESTION_WINDOW_DAYS - 1));

  const [campaigns, insights] = await Promise.all([
    options.source.listCampaigns(),
    options.source.getDailyInsights({ from, to }),
  ]);
  const account = deriveMetrics(sumInsights(insights));
  const suggestions = suggestOptimizations(campaignRows(campaigns, insights), account.costPerLeadCents, SUGGESTION_WINDOW_DAYS);

  const since = new Date(now.getTime() - REJECTION_COOLDOWN_DAYS * 86_400_000);
  const blocked = await options.queue.listBlockedKeys(options.organizationId, since);
  const fresh = suggestions.filter((s) => !blocked.has(blockKey(s.campaignId, s.action.type)));

  if (fresh.length > 0) await options.queue.insertSuggestions(options.organizationId, fresh);
  return fresh.length;
}
