"use server";

import { headers } from "next/headers";
import { redirect } from "next/navigation";
import { credentialsSchema, firstIssue, type FormState } from "@/lib/auth/credentials";
import { safeNextPath } from "@/lib/auth/routes";
import { createClient } from "@/lib/supabase/server";

function readCredentials(formData: FormData) {
  return credentialsSchema.safeParse({ email: formData.get("email"), password: formData.get("password") });
}

export async function signIn(_state: FormState, formData: FormData): Promise<FormState> {
  const parsed = readCredentials(formData);
  if (!parsed.success) return { error: firstIssue(parsed.error) };

  const supabase = await createClient();
  const { error } = await supabase.auth.signInWithPassword(parsed.data);
  if (error) {
    return {
      error:
        error.code === "email_not_confirmed"
          ? "Confirme seu e-mail pelo link que enviamos antes de entrar."
          : "E-mail ou senha incorretos.",
    };
  }

  redirect(safeNextPath(formData.get("next")));
}

export async function signUp(_state: FormState, formData: FormData): Promise<FormState> {
  const parsed = readCredentials(formData);
  if (!parsed.success) return { error: firstIssue(parsed.error) };

  const origin = (await headers()).get("origin");
  const supabase = await createClient();
  const { data, error } = await supabase.auth.signUp({
    ...parsed.data,
    options: origin ? { emailRedirectTo: `${origin}/auth/confirm` } : undefined,
  });
  if (error) {
    return {
      error:
        error.code === "signup_disabled"
          ? "Novos cadastros estão desativados."
          : "Não foi possível criar a conta. Tente novamente.",
    };
  }

  // With e-mail confirmation disabled Supabase returns a session right away.
  if (data.session) redirect("/onboarding");
  return { message: `Enviamos um link de confirmação para ${parsed.data.email}.` };
}
