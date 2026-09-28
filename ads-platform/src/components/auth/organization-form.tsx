"use client";

import { useActionState } from "react";
import type { FormState } from "@/lib/auth/credentials";

export function OrganizationForm({ action }: { action: (state: FormState, formData: FormData) => Promise<FormState> }) {
  const [state, formAction, pending] = useActionState(action, {});

  return (
    <form action={formAction} className="flex flex-col gap-4">
      <label className="flex flex-col gap-1.5 text-sm font-medium">
        Nome da empresa
        <input
          name="name"
          required
          minLength={2}
          maxLength={120}
          autoComplete="organization"
          className="rounded-md border border-border bg-card px-3 py-2 font-normal outline-none focus:border-primary"
        />
      </label>
      {state.error && (
        <p role="alert" className="rounded-md bg-negative/10 px-3 py-2 text-sm text-negative">
          {state.error}
        </p>
      )}
      <button
        type="submit"
        disabled={pending}
        className="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground disabled:opacity-60"
      >
        {pending ? "Criando…" : "Continuar"}
      </button>
    </form>
  );
}
