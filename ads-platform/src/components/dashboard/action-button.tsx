"use client";

import { useActionState } from "react";
import type { FormState } from "@/lib/auth/credentials";
import { cn } from "@/lib/utils";

interface ActionButtonProps {
  action: (state: FormState) => Promise<FormState>;
  label: string;
  pendingLabel: string;
  variant?: "primary" | "secondary";
}

/** Button that runs a server action and shows its result message inline. */
export function ActionButton({ action, label, pendingLabel, variant = "secondary" }: ActionButtonProps) {
  const [state, formAction, pending] = useActionState(action, {});

  return (
    <form action={formAction} className="flex flex-wrap items-center gap-2">
      <button
        type="submit"
        disabled={pending}
        className={cn(
          "rounded-md px-3 py-1.5 text-xs font-medium whitespace-nowrap disabled:opacity-60",
          variant === "primary"
            ? "bg-primary text-primary-foreground"
            : "border border-border bg-card text-foreground hover:bg-muted",
        )}
      >
        {pending ? pendingLabel : label}
      </button>
      {state.error && (
        <span role="alert" className="text-xs text-negative">
          {state.error}
        </span>
      )}
      {state.message && (
        <span role="status" className="text-xs text-muted-foreground">
          {state.message}
        </span>
      )}
    </form>
  );
}
