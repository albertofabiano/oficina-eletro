import { createClient } from "@supabase/supabase-js";
import { z } from "zod";
import type { Database } from "./database.types";

const adminConfigSchema = z.object({
  url: z.url({ message: "NEXT_PUBLIC_SUPABASE_URL ausente ou inválida" }),
  secretKey: z.string().min(20, { message: "SUPABASE_SECRET_KEY ausente" }),
});

/**
 * Service-role client for background jobs (collector, approval executor).
 * It bypasses RLS, so it must only run on the server and never reach the browser.
 */
export function createAdminClient(source: Record<string, string | undefined> = process.env) {
  const { url, secretKey } = adminConfigSchema.parse({
    url: source.SUPABASE_URL ?? source.NEXT_PUBLIC_SUPABASE_URL,
    secretKey: source.SUPABASE_SECRET_KEY ?? source.SUPABASE_SERVICE_ROLE_KEY,
  });
  return createClient<Database>(url, secretKey, {
    auth: { persistSession: false, autoRefreshToken: false },
  });
}

export type AdminClient = ReturnType<typeof createAdminClient>;
