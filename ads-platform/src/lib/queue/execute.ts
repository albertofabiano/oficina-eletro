import type { AdPlatform } from "@/lib/ads/platform";
import { parseActionPayload } from "@/lib/optimization/payload";
import type { ApprovedRequest, ExecutionTarget, QueueStore } from "./store";

export type ExecutionResult =
  | { requestId: string; status: "executed"; dryRun: boolean }
  | { requestId: string; status: "failed"; error: string };

/**
 * Runs approved requests. This is the only code path allowed to call the
 * platform write methods. With DRY_RUN nothing is sent: the request is only
 * recorded as executed in simulation.
 */
export async function executeApprovedRequests(options: {
  queue: QueueStore;
  platformFor: (target: ExecutionTarget) => AdPlatform | Promise<AdPlatform>;
  dryRun: boolean;
  filter?: { organizationId?: string; requestId?: string };
  now?: () => Date;
}): Promise<ExecutionResult[]> {
  const now = options.now ?? (() => new Date());
  const requests = await options.queue.listApproved(options.filter ?? {});
  const results: ExecutionResult[] = [];

  for (const request of requests) {
    try {
      await executeOne(request, options.queue, options.platformFor, options.dryRun);
      await options.queue.markExecuted(request, { dryRun: options.dryRun, at: now() });
      results.push({ requestId: request.id, status: "executed", dryRun: options.dryRun });
    } catch (error) {
      const message = error instanceof Error ? error.message : String(error);
      await options.queue.markFailed(request, message, now());
      results.push({ requestId: request.id, status: "failed", error: message });
    }
  }
  return results;
}

async function executeOne(
  request: ApprovedRequest,
  queue: QueueStore,
  platformFor: (target: ExecutionTarget) => AdPlatform | Promise<AdPlatform>,
  dryRun: boolean,
) {
  // Validate the stored payload even in dry run, so simulations surface bad data too.
  const action = parseActionPayload(request.actionType, request.payload);
  const target = await queue.loadTarget(request.campaignId);
  if (!target) throw new Error("Campanha não encontrada.");
  if (dryRun) return;

  const platform = await platformFor(target);
  const account = { externalId: target.accountExternalId };
  switch (action.type) {
    case "pause_campaign":
      await platform.setCampaignStatus(account, target.campaignExternalId, "paused");
      await queue.applyToCampaign(request.campaignId, { status: "paused" });
      break;
    case "resume_campaign":
      await platform.setCampaignStatus(account, target.campaignExternalId, "active");
      await queue.applyToCampaign(request.campaignId, { status: "active" });
      break;
    case "update_daily_budget":
      await platform.setDailyBudget(account, target.campaignExternalId, action.toCents);
      await queue.applyToCampaign(request.campaignId, { dailyBudgetCents: action.toCents });
      break;
  }
}
