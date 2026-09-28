"use server";

import { revalidatePath } from "next/cache";
import { z } from "zod";
import { MetaApiError } from "@/lib/ads/meta/graph-client";
import { checkAccountRequirements, connectMetaSchema } from "@/lib/ads/meta/connect";
import { MetaAdsPlatform } from "@/lib/ads/meta/meta-platform";
import { createSdkGraphClient } from "@/lib/ads/meta/sdk-client";
import { firstIssue, type FormState } from "@/lib/auth/credentials";
import { canManageAccounts, getMembershipRole, NOT_ALLOWED_MESSAGE } from "@/lib/auth/membership";
import { requireOrganization } from "@/lib/auth/session";
import { adminOrError, runManualSync } from "@/lib/sync/manual";

/**
 * Validates the token against the account on Meta, stores it in Vault and runs
 * the first collection. The token is never returned or logged.
 */
export async function connectMetaAccount(_state: FormState, formData: FormData): Promise<FormState> {
  const organization = await requireOrganization();
  const membership = await getMembershipRole(organization.id);
  if (!membership || !canManageAccounts(membership.role)) return { error: NOT_ALLOWED_MESSAGE };

  const parsed = connectMetaSchema.safeParse({
    adAccountId: formData.get("adAccountId") ?? "",
    accessToken: formData.get("accessToken") ?? "",
  });
  if (!parsed.success) return { error: firstIssue(parsed.error) };
  const { adAccountId, accessToken } = parsed.data;

  let info;
  try {
    info = await new MetaAdsPlatform(createSdkGraphClient(accessToken)).getAccountInfo(adAccountId);
  } catch (error) {
    return { error: error instanceof MetaApiError ? error.message : "Não foi possível consultar a conta na Meta." };
  }
  const problem = checkAccountRequirements(info);
  if (problem) return { error: problem };

  const admin = adminOrError();
  if (!admin.db) return { error: admin.error };
  const { error } = await admin.db.rpc("connect_ad_account", {
    p_organization_id: organization.id,
    p_user_id: membership.userId,
    p_platform: "meta",
    p_external_id: adAccountId,
    p_name: info.name,
    p_access_token: accessToken,
  });
  if (error) {
    console.error("connect_ad_account failed", error.code);
    return { error: "Não foi possível salvar a conexão. Tente novamente." };
  }

  const sync = await runManualSync(organization.id);
  revalidatePath("/", "layout");
  if (sync.error) return { error: `Conta "${info.name}" conectada, mas a primeira coleta falhou. ${sync.error}` };
  return { message: `Conta "${info.name}" conectada e dados importados.` };
}

const accountIdSchema = z.uuid();

/** Meta accounts: stop collecting and delete the token (history is kept). Demo accounts: remove with their data. */
export async function removeAdAccount(_state: FormState, formData: FormData): Promise<FormState> {
  const organization = await requireOrganization();
  const membership = await getMembershipRole(organization.id);
  if (!membership || !canManageAccounts(membership.role)) return { error: NOT_ALLOWED_MESSAGE };

  const id = accountIdSchema.safeParse(formData.get("accountId"));
  if (!id.success) return { error: "Conta inválida." };

  const admin = adminOrError();
  if (!admin.db) return { error: admin.error };
  const { data: account } = await admin.db
    .from("ad_accounts")
    .select("id, platform")
    .eq("id", id.data)
    .eq("organization_id", organization.id)
    .maybeSingle();
  if (!account) return { error: "Conta não encontrada." };

  if (account.platform === "fake") {
    const { error } = await admin.db.from("ad_accounts").delete().eq("id", account.id);
    if (error) return { error: "Não foi possível remover a conta de demonstração." };
    await admin.db.from("audit_log").insert({
      organization_id: organization.id,
      actor_user_id: membership.userId,
      action: "ad_account.removed",
      entity_type: "ad_account",
      entity_id: account.id,
      details: { platform: "fake" },
    });
  } else {
    const { error } = await admin.db.rpc("disconnect_ad_account", { p_ad_account_id: account.id, p_user_id: membership.userId });
    if (error) return { error: "Não foi possível desconectar a conta." };
  }
  revalidatePath("/", "layout");
  return { message: account.platform === "fake" ? "Conta de demonstração removida." : "Conta desconectada." };
}
