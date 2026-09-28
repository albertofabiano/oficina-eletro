"use server";

import { redirect } from "next/navigation";
import { firstIssue, organizationNameSchema, type FormState } from "@/lib/auth/credentials";
import { createClient } from "@/lib/supabase/server";

export async function createOrganization(_state: FormState, formData: FormData): Promise<FormState> {
  const parsed = organizationNameSchema.safeParse(formData.get("name"));
  if (!parsed.success) return { error: firstIssue(parsed.error) };

  const supabase = await createClient();
  const { error } = await supabase.rpc("create_organization", { org_name: parsed.data });
  if (error) return { error: "Não foi possível criar a empresa. Tente novamente." };

  redirect("/dashboard");
}
