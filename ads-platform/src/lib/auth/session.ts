import "server-only";
import { cache } from "react";
import { redirect } from "next/navigation";
import { createClient } from "@/lib/supabase/server";

export const getCurrentUser = cache(async () => {
  const supabase = await createClient();
  const {
    data: { user },
  } = await supabase.auth.getUser();
  return user;
});

export async function requireUser() {
  const user = await getCurrentUser();
  if (!user) redirect("/login");
  return user;
}

/** The user's organization. RLS only returns organizations the user belongs to. */
export const getCurrentOrganization = cache(async () => {
  const supabase = await createClient();
  const { data, error } = await supabase
    .from("organizations")
    .select("id, name")
    .order("created_at")
    .limit(1)
    .maybeSingle();
  if (error) throw error;
  return data;
});

export async function requireOrganization() {
  await requireUser();
  const organization = await getCurrentOrganization();
  if (!organization) redirect("/onboarding");
  return organization;
}
