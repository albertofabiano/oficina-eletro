import { getAdPlatform } from "@/lib/ads/registry";
import { vaultTokenLoader } from "@/lib/ads/token";
import { SupabaseDashboardDataSource } from "@/lib/dashboard/supabase-data-source";
import { loadEnv } from "@/lib/env";
import { executeApprovedRequests } from "@/lib/queue/execute";
import { generateSuggestions } from "@/lib/queue/generate";
import { SupabaseQueueStore } from "@/lib/queue/supabase-store";
import type { AdminClient } from "@/lib/supabase/admin";
import { SupabaseSyncStore } from "@/lib/sync/supabase-store";
import { syncAllAccounts } from "@/lib/sync/sync-account";

/**
 * Collects ad data and then queues new optimization suggestions. Used by the
 * daily job (all organizations) and by the manual sync button (one organization).
 */
export async function syncAndSuggest(db: AdminClient, organizationId?: string) {
  const syncResults = await syncAllAccounts({
    store: new SupabaseSyncStore(db),
    platformFor: (account) => getAdPlatform(account, vaultTokenLoader(db)),
    organizationId,
  });

  const queue = new SupabaseQueueStore(db);
  const organizations = organizationId ? [organizationId] : await queue.listOrganizationsWithAccounts();
  let suggestions = 0;
  for (const org of organizations) {
    suggestions += await generateSuggestions({
      source: new SupabaseDashboardDataSource(db, org),
      queue,
      organizationId: org,
    });
  }
  return { syncResults, suggestions };
}

/** Executes approved requests, honoring DRY_RUN. */
export function executeApproved(db: AdminClient, filter: { organizationId?: string; requestId?: string } = {}) {
  return executeApprovedRequests({
    queue: new SupabaseQueueStore(db),
    platformFor: (target) => getAdPlatform({ id: target.adAccountId, platform: target.platform }, vaultTokenLoader(db)),
    dryRun: loadEnv().DRY_RUN,
    filter,
  });
}
