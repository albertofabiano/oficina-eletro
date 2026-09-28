import { logger, schedules } from "@trigger.dev/sdk";
import { executeApproved, syncAndSuggest } from "@/lib/jobs";
import { createAdminClient } from "@/lib/supabase/admin";

/**
 * Daily routine: collect every active ad account (re-collecting the last 7
 * days), queue new optimization suggestions, and execute requests that were
 * approved but not executed yet (e.g. after a transient failure).
 */
export const syncAdAccounts = schedules.task({
  id: "sync-ad-accounts",
  cron: { pattern: "0 6 * * *", timezone: "America/Sao_Paulo" },
  run: async () => {
    const db = createAdminClient();
    const { syncResults, suggestions } = await syncAndSuggest(db);

    const failed = syncResults.filter((r) => !r.ok);
    for (const failure of failed) logger.error("ad account sync failed", { failure });

    const executions = await executeApproved(db);
    for (const result of executions.filter((r) => r.status === "failed")) {
      logger.error("approved request failed", { result });
    }

    logger.info("daily routine finished", {
      accounts: syncResults.length,
      failedAccounts: failed.length,
      suggestions,
      executed: executions.length,
    });

    if (failed.length > 0 && failed.length === syncResults.length) {
      throw new Error(`all ${failed.length} ad account syncs failed`);
    }
    return { accounts: syncResults.length, failedAccounts: failed.length, suggestions, executed: executions.length };
  },
});
