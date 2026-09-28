"use client";

import { useActionState } from "react";
import type { FormState } from "@/lib/auth/credentials";

interface AuthFormProps {
  action: (state: FormState, formData: FormData) => Promise<FormState>;
  submitLabel: string;
  pendingLabel: string;
  next?: string;
  passwordAutoComplete: "current-password" | "new-password";
}

export function AuthForm({ action, submitLabel, pendingLabel, next, passwordAutoComplete }: AuthFormProps) {
  const [state, formAction, pending] = useActionState(action, {});

  return (
    <form action={formAction} className="flex flex-col gap-4">
      {next && <input type="hidden" name="next" value={next} />}
      <label className="flex flex-col gap-1.5 text-sm font-medium">
        E-mail
        <input
          name="email"
          type="email"
          required
          autoComplete="email"
          className="rounded-md border border-border bg-card px-3 py-2 font-normal outline-none focus:border-primary"
        />
      </label>
      <label className="flex flex-col gap-1.5 text-sm font-medium">
        Senha
        <input
          name="password"
          type="password"
          required
          minLength={8}
          autoComplete={passwordAutoComplete}
          className="rounded-md border border-border bg-card px-3 py-2 font-normal outline-none focus:border-primary"
        />
      </label>
      {state.error && (
        <p role="alert" className="rounded-md bg-negative/10 px-3 py-2 text-sm text-negative">
          {state.error}
        </p>
      )}
      {state.message && (
        <p role="status" className="rounded-md bg-positive/10 px-3 py-2 text-sm text-positive">
          {state.message}
        </p>
      )}
      <button
        type="submit"
        disabled={pending}
        className="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground disabled:opacity-60"
      >
        {pending ? pendingLabel : submitLabel}
      </button>
    </form>
  );
}
