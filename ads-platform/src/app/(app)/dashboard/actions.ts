"use server";

import type { FormState } from "@/lib/auth/credentials";
import { canManageAccounts, getMembershipRole, NOT_ALLOWED_MESSAGE } from "@/lib/auth/membership";
import { requireOrganization } from "@/lib/auth/session";
import { createClient } from "@/lib/supabase/server";
import { adminOrError, runManualSync } from "@/lib/sync/manual";

/** Minimum interval between manual syncs of the same organization. */
const MANUAL_SYNC_COOLDOWN_MS = 60_000;

export async function syncNow(_state: FormState): Promise<FormState> {
  const organization = await requireOrganization();
  const supabase = await createClient();
  const { data: accounts, error } = await supabase
    .from("ad_accounts")
    .select("last_synced_at")
    .eq("organization_id", organization.id)
    .eq("status", "active");
  if (error) return { error: "Não foi possível ler as contas de anúncio." };

  // Only block when every account was just collected: a newly connected one must sync right away.
  const oldestSync = Math.min(...accounts.map((a) => (a.last_synced_at ? Date.parse(a.last_synced_at) : 0)));
  if (accounts.length > 0 && Date.now() - oldestSync < MANUAL_SYNC_COOLDOWN_MS) {
    return { message: "Os dados acabaram de ser atualizados. Aguarde um minuto para sincronizar de novo." };
  }
  return runManualSync(organization.id);
}

export async function connectDemoAccount(_state: FormState): Promise<FormState> {
  const organization = await requireOrganization();
  const membership = await getMembershipRole(organization.id);
  if (!canManageAccounts(membership?.role)) return { error: NOT_ALLOWED_MESSAGE };

  const admin = adminOrError();
  if (!admin.db) return { error: admin.error };

  const { data: account, error } = await admin.db
    .from("ad_accounts")
    .upsert(
      {
        organization_id: organization.id,
        platform: "fake",
        external_id: `demo_${organization.id.slice(0, 8)}`,
        name: "Conta de demonstração",
      },
      { onConflict: "organization_id,platform,external_id", ignoreDuplicates: true },
    )
    .select("id")
    .maybeSingle();
  if (error) return { error: "Não foi possível conectar a conta de demonstração." };

  if (account) {
    await admin.db.from("audit_log").insert({
      organization_id: organization.id,
      actor_user_id: membership?.userId ?? null,
      action: "ad_account.connected",
      entity_type: "ad_account",
      entity_id: account.id,
      details: { platform: "fake" },
    });
  }
  return runManualSync(organization.id);
}
