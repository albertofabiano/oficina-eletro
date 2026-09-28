import { logger, schedules } from "@trigger.dev/sdk";
import { getAdPlatform } from "@/lib/ads/registry";
import { createAdminClient } from "@/lib/supabase/admin";
import { SupabaseSyncStore } from "@/lib/sync/supabase-store";
import { syncAllAccounts } from "@/lib/sync/sync-account";

/**
 * Daily collection of every active ad account. Each run re-collects the last
 * 7 days because platforms keep revising attributed results.
 */
export const syncAdAccounts = schedules.task({
  id: "sync-ad-accounts",
  cron: { pattern: "0 6 * * *", timezone: "America/Sao_Paulo" },
  run: async () => {
    const results = await syncAllAccounts({
      store: new SupabaseSyncStore(createAdminClient()),
      platformFor: (account) => getAdPlatform(account.platform),
    });

    const failed = results.filter((r) => !r.ok);
    for (const failure of failed) logger.error("ad account sync failed", { failure });
    logger.info("ad account sync finished", { total: results.length, failed: failed.length });

    if (failed.length > 0 && failed.length === results.length) {
      throw new Error(`all ${failed.length} ad account syncs failed`);
    }
    return { total: results.length, failed: failed.length };
  },
});
