import type { ReactNode } from "react";

export function AuthCard({ title, description, children }: { title: string; description: string; children: ReactNode }) {
  return (
    <main className="grid min-h-dvh place-items-center p-4">
      <div className="w-full max-w-sm rounded-xl border border-border bg-card p-6 shadow-sm">
        <div className="mb-6 flex items-center gap-2 font-semibold">
          <span className="grid size-7 place-items-center rounded-md bg-primary text-xs text-primary-foreground">TP</span>
          Tráfego Pago
        </div>
        <h1 className="text-lg font-semibold">{title}</h1>
        <p className="mb-6 text-sm text-muted-foreground">{description}</p>
        {children}
      </div>
    </main>
  );
}
