import "server-only";
import { getCurrentUser } from "./session";
import { createClient } from "@/lib/supabase/server";

/** The signed-in user's role in an organization, or null when not a member (RLS-checked). */
export async function getMembershipRole(organizationId: string) {
  const user = await getCurrentUser();
  if (!user) return null;
  const supabase = await createClient();
  const { data } = await supabase
    .from("organization_members")
    .select("role")
    .eq("organization_id", organizationId)
    .eq("user_id", user.id)
    .maybeSingle();
  return data ? { userId: user.id, role: data.role } : null;
}

export const canManageAccounts = (role: string | undefined) => role === "owner" || role === "admin";

export const NOT_ALLOWED_MESSAGE = "Apenas o dono ou um administrador da empresa pode gerenciar contas de anúncios.";
