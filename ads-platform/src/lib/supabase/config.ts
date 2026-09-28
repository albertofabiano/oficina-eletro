import { z } from "zod";

const supabaseConfigSchema = z.object({
  url: z.url({ message: "NEXT_PUBLIC_SUPABASE_URL ausente ou inválida" }),
  key: z.string().min(20, { message: "NEXT_PUBLIC_SUPABASE_ANON_KEY ausente" }),
});

export type SupabaseConfig = z.infer<typeof supabaseConfigSchema>;

/** Public (browser-safe) Supabase settings. The service role key never goes through here. */
export function supabaseConfig(source: Record<string, string | undefined> = process.env): SupabaseConfig {
  return supabaseConfigSchema.parse({
    url: source.NEXT_PUBLIC_SUPABASE_URL,
    key: source.NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY ?? source.NEXT_PUBLIC_SUPABASE_ANON_KEY,
  });
}
