import type { AdPlatform } from "@/lib/ads/platform";
import { addDays, todayInSaoPaulo, type IsoDate } from "@/lib/dates";
import type { SyncAccount, SyncStore, SyncSummary } from "./store";

/** Attribution keeps changing for recent days, so every run re-collects this window. */
export const RECOLLECT_DAYS = 7;
/** First collection imports enough history for the 30-day view and its comparison period. */
export const BACKFILL_DAYS = 60;

export function collectionRange(today: IsoDate, lastSyncedAt: string | null): { from: IsoDate; to: IsoDate } {
  const days = lastSyncedAt ? RECOLLECT_DAYS : BACKFILL_DAYS;
  return { from: addDays(today, -(days - 1)), to: today };
}

export async function syncAdAccount(options: {
  store: SyncStore;
  platform: AdPlatform;
  account: SyncAccount;
  now?: Date;
}): Promise<SyncSummary> {
  const { store, platform, account } = options;
  const now = options.now ?? new Date();
  const range = collectionRange(todayInSaoPaulo(now), account.lastSyncedAt);
  const ref = { externalId: account.externalId };

  const campaigns = await platform.listCampaigns(ref);
  const campaignIds = await store.upsertCampaigns(account, campaigns);

  const insights = await platform.getDailyInsights(ref, range);
  const rows = insights.flatMap((row) => {
    const campaignId = campaignIds.get(row.campaignExternalId);
    return campaignId ? [{ ...row, campaignId }] : [];
  });
  await store.upsertInsights(account, rows);

  const summary = { ...range, campaigns: campaigns.length, insightRows: rows.length };
  await store.markSynced(account, now, summary);
  return summary;
}

export type SyncResult =
  | { accountId: string; ok: true; summary: SyncSummary }
  | { accountId: string; ok: false; error: string };

/** Syncs every active account; one failing account does not stop the others. */
export async function syncAllAccounts(options: {
  store: SyncStore;
  platformFor: (account: SyncAccount) => AdPlatform;
  organizationId?: string;
  now?: Date;
}): Promise<SyncResult[]> {
  const accounts = await options.store.listActiveAccounts({ organizationId: options.organizationId });
  const results: SyncResult[] = [];
  for (const account of accounts) {
    try {
      const summary = await syncAdAccount({
        store: options.store,
        platform: options.platformFor(account),
        account,
        now: options.now,
      });
      results.push({ accountId: account.id, ok: true, summary });
    } catch (error) {
      results.push({ accountId: account.id, ok: false, error: error instanceof Error ? error.message : String(error) });
    }
  }
  return results;
}
