import "server-only";
import { revalidatePath } from "next/cache";
import type { FormState } from "@/lib/auth/credentials";
import { syncAndSuggest } from "@/lib/jobs";
import { createAdminClient } from "@/lib/supabase/admin";

export function adminOrError() {
  try {
    return { db: createAdminClient() };
  } catch {
    return { error: "A chave de serviço do Supabase (SUPABASE_SECRET_KEY) não está configurada no servidor." };
  }
}

/** Collects one organization now and reports the outcome for the UI. Callers check authorization. */
export async function runManualSync(organizationId: string): Promise<FormState> {
  const admin = adminOrError();
  if (!admin.db) return { error: admin.error };
  const { syncResults, suggestions } = await syncAndSuggest(admin.db, organizationId);
  revalidatePath("/", "layout");
  const failed = syncResults.filter((r) => !r.ok);
  if (failed.length > 0) {
    // Error messages are built without tokens (see MetaApiError), so they are safe to log and show.
    console.error("sync failed", failed);
    const first = failed[0]!.error.slice(0, 200);
    return {
      error:
        failed.length === 1
          ? `Falha ao sincronizar: ${first}`
          : `Falha ao sincronizar ${failed.length} contas. Primeiro erro: ${first}`,
    };
  }
  return {
    message:
      suggestions > 0
        ? `Dados atualizados. ${suggestions} nova(s) sugestão(ões) aguardando aprovação.`
        : "Dados atualizados.",
  };
}
