"use client";

import { useActionState } from "react";
import type { FormState } from "@/lib/auth/credentials";

interface RemoveAccountButtonProps {
  accountId: string;
  label: string;
  confirmText: string;
  action: (state: FormState, formData: FormData) => Promise<FormState>;
}

export function RemoveAccountButton({ accountId, label, confirmText, action }: RemoveAccountButtonProps) {
  const [state, formAction, pending] = useActionState(action, {});

  return (
    <form
      action={formAction}
      onSubmit={(event) => {
        if (!window.confirm(confirmText)) event.preventDefault();
      }}
      className="flex flex-col items-end gap-1"
    >
      <input type="hidden" name="accountId" value={accountId} />
      <button
        type="submit"
        disabled={pending}
        className="rounded-md border border-border bg-card px-3 py-1.5 text-xs font-medium hover:bg-muted disabled:opacity-60"
      >
        {pending ? "Aguarde…" : label}
      </button>
      {state.error && (
        <p role="alert" className="text-xs text-negative">
          {state.error}
        </p>
      )}
    </form>
  );
}
