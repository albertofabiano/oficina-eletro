"use server";

import { revalidatePath } from "next/cache";
import type { FormState } from "@/lib/auth/credentials";
import { getCurrentUser, requireOrganization } from "@/lib/auth/session";
import { createAdminClient } from "@/lib/supabase/admin";
import { createClient } from "@/lib/supabase/server";
import { syncAndSuggest } from "@/lib/jobs";

/** Minimum interval between manual syncs of the same organization. */
const MANUAL_SYNC_COOLDOWN_MS = 60_000;

function adminOrError() {
  try {
    return { db: createAdminClient() };
  } catch {
    return { error: "A chave de serviço do Supabase (SUPABASE_SECRET_KEY) não está configurada no servidor." };
  }
}

async function runSync(organizationId: string): Promise<FormState> {
  const admin = adminOrError();
  if (!admin.db) return { error: admin.error };
  const { syncResults, suggestions } = await syncAndSuggest(admin.db, organizationId);
  revalidatePath("/", "layout");
  const failed = syncResults.filter((r) => !r.ok);
  if (failed.length > 0) {
    console.error("sync failed", failed);
    return { error: `Falha ao sincronizar ${failed.length} conta(s). Tente novamente em alguns minutos.` };
  }
  return {
    message:
      suggestions > 0
        ? `Dados atualizados. ${suggestions} nova(s) sugestão(ões) aguardando aprovação.`
        : "Dados atualizados.",
  };
}

export async function syncNow(_state: FormState): Promise<FormState> {
  const organization = await requireOrganization();
  const supabase = await createClient();
  const { data: accounts, error } = await supabase
    .from("ad_accounts")
    .select("last_synced_at")
    .eq("organization_id", organization.id);
  if (error) return { error: "Não foi possível ler as contas de anúncio." };

  const lastSync = Math.max(0, ...accounts.map((a) => (a.last_synced_at ? Date.parse(a.last_synced_at) : 0)));
  if (Date.now() - lastSync < MANUAL_SYNC_COOLDOWN_MS) {
    return { message: "Os dados acabaram de ser atualizados. Aguarde um minuto para sincronizar de novo." };
  }
  return runSync(organization.id);
}

export async function connectDemoAccount(_state: FormState): Promise<FormState> {
  const organization = await requireOrganization();
  const user = await getCurrentUser();
  const supabase = await createClient();

  // RLS already limits reads to the user's organizations; also require an admin role to add accounts.
  const { data: membership } = await supabase
    .from("organization_members")
    .select("role")
    .eq("organization_id", organization.id)
    .eq("user_id", user?.id ?? "")
    .maybeSingle();
  if (!membership || membership.role === "member") {
    return { error: "Apenas o dono ou um administrador da empresa pode conectar contas." };
  }

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
      actor_user_id: user?.id ?? null,
      action: "ad_account.connected",
      entity_type: "ad_account",
      entity_id: account.id,
      details: { platform: "fake" },
    });
  }
  return runSync(organization.id);
}
