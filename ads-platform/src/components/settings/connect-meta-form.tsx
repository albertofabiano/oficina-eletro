"use client";

import { useActionState } from "react";
import type { FormState } from "@/lib/auth/credentials";

const inputClass =
  "rounded-md border border-border bg-card px-3 py-2 text-sm font-normal outline-none focus:border-primary";

export function ConnectMetaForm({ action }: { action: (state: FormState, formData: FormData) => Promise<FormState> }) {
  const [state, formAction, pending] = useActionState(action, {});

  return (
    <form action={formAction} className="flex flex-col gap-4">
      <label className="flex flex-col gap-1.5 text-sm font-medium">
        ID da conta de anúncios
        <input
          name="adAccountId"
          required
          inputMode="numeric"
          autoComplete="off"
          placeholder="act_1234567890"
          className={inputClass}
        />
        <span className="text-xs font-normal text-muted-foreground">
          Aparece no Gerenciador de Anúncios, ao lado do nome da conta.
        </span>
      </label>
      <label className="flex flex-col gap-1.5 text-sm font-medium">
        Token de acesso
        <input
          name="accessToken"
          type="password"
          required
          autoComplete="off"
          spellCheck={false}
          placeholder="EAAB…"
          className={inputClass}
        />
        <span className="text-xs font-normal text-muted-foreground">
          Guardado criptografado e nunca mostrado de novo. Para trocar, conecte a mesma conta com o token novo.
        </span>
      </label>
      <div className="flex flex-wrap items-center gap-3">
        <button
          type="submit"
          disabled={pending}
          className="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-60"
        >
          {pending ? "Validando e importando…" : "Conectar conta"}
        </button>
        {state.error && (
          <p role="alert" className="text-sm text-negative">
            {state.error}
          </p>
        )}
        {state.message && (
          <p role="status" className="text-sm text-positive">
            {state.message}
          </p>
        )}
      </div>
    </form>
  );
}
