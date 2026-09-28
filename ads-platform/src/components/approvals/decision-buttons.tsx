"use client";

import { useActionState } from "react";
import { Check, X } from "lucide-react";
import type { FormState } from "@/lib/auth/credentials";

interface DecisionButtonsProps {
  requestId: string;
  action: (state: FormState, formData: FormData) => Promise<FormState>;
}

export function DecisionButtons({ requestId, action }: DecisionButtonsProps) {
  const [state, formAction, pending] = useActionState(action, {});

  return (
    <form action={formAction} className="flex flex-col items-end gap-1.5">
      <input type="hidden" name="requestId" value={requestId} />
      <div className="flex gap-2">
        <button
          type="submit"
          name="decision"
          value="rejected"
          disabled={pending}
          className="inline-flex items-center gap-1 rounded-md border border-border bg-card px-3 py-1.5 text-xs font-medium hover:bg-muted disabled:opacity-60"
        >
          <X className="size-3.5" aria-hidden />
          Rejeitar
        </button>
        <button
          type="submit"
          name="decision"
          value="approved"
          disabled={pending}
          className="inline-flex items-center gap-1 rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground disabled:opacity-60"
        >
          <Check className="size-3.5" aria-hidden />
          {pending ? "Registrando…" : "Aprovar"}
        </button>
      </div>
      {state.error && (
        <p role="alert" className="text-xs text-negative">
          {state.error}
        </p>
      )}
      {state.message && (
        <p role="status" className="text-xs text-muted-foreground">
          {state.message}
        </p>
      )}
    </form>
  );
}
