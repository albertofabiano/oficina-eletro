import type { AdminClient } from "@/lib/supabase/admin";
import type { TokenLoader } from "./registry";

/**
 * Reads an access token from Supabase Vault through a function only the service
 * role may execute. The value must never be logged or sent to the browser.
 */
export function vaultTokenLoader(db: AdminClient): TokenLoader {
  return async (adAccountId) => {
    const { data, error } = await db.rpc("get_ad_account_token", { p_ad_account_id: adAccountId });
    if (error) throw new Error("Não foi possível ler o token da conta de anúncios.");
    if (!data) throw new Error("Conta de anúncios sem token. Reconecte a conta em Configurações.");
    return data;
  };
}
