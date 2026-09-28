import type { SupabaseClient } from "@supabase/supabase-js";
import { parseActionPayload } from "@/lib/optimization/payload";
import type { ActionPayload } from "@/lib/optimization/types";
import type { Database } from "@/lib/supabase/database.types";

type Status = Database["public"]["Tables"]["action_requests"]["Row"]["status"];

export interface QueueItem {
  id: string;
  status: Status;
  campaignName: string;
  action: ActionPayload | null;
  reason: string;
  requestedAt: string;
  decidedAt: string | null;
  decidedByMe: boolean;
  executedAt: string | null;
  dryRun: boolean | null;
  error: string | null;
}

/** Reads the approval queue as the signed-in user (RLS scopes it to their organization). */
export async function listQueue(
  db: SupabaseClient<Database>,
  organizationId: string,
  userId: string,
): Promise<{ pending: QueueItem[]; history: QueueItem[] }> {
  const [requests, campaigns] = await Promise.all([
    db
      .from("action_requests")
      .select("id, status, campaign_id, action_type, payload, reason, requested_at, decided_by, decided_at, executed_at, dry_run, error")
      .eq("organization_id", organizationId)
      .order("requested_at", { ascending: false })
      .limit(100),
    db.from("campaigns").select("id, name").eq("organization_id", organizationId),
  ]);
  if (requests.error) throw requests.error;
  if (campaigns.error) throw campaigns.error;

  const names = new Map(campaigns.data.map((c) => [c.id, c.name]));
  const items = requests.data.map((r): QueueItem => {
    let action: ActionPayload | null = null;
    try {
      action = parseActionPayload(r.action_type, r.payload);
    } catch {
      action = null;
    }
    return {
      id: r.id,
      status: r.status,
      campaignName: names.get(r.campaign_id) ?? "Campanha removida",
      action,
      reason: r.reason,
      requestedAt: r.requested_at,
      decidedAt: r.decided_at,
      decidedByMe: r.decided_by === userId,
      executedAt: r.executed_at,
      dryRun: r.dry_run,
      error: r.error,
    };
  });
  return {
    pending: items.filter((i) => i.status === "pending"),
    history: items.filter((i) => i.status !== "pending").slice(0, 30),
  };
}

export async function countPending(db: SupabaseClient<Database>, organizationId: string): Promise<number> {
  const { count, error } = await db
    .from("action_requests")
    .select("id", { count: "exact", head: true })
    .eq("organization_id", organizationId)
    .eq("status", "pending");
  if (error) throw error;
  return count ?? 0;
}
