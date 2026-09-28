import { AlertTriangle, CheckCircle2, OctagonAlert } from "lucide-react";
import type { DashboardAlert } from "@/lib/dashboard/alerts";
import { cn } from "@/lib/utils";

export function AlertsPanel({ alerts }: { alerts: DashboardAlert[] }) {
  if (alerts.length === 0) {
    return (
      <div className="flex items-center gap-2 rounded-lg bg-positive/10 p-3 text-sm text-positive">
        <CheckCircle2 className="size-4 shrink-0" aria-hidden />
        Nenhum alerta no período.
      </div>
    );
  }

  return (
    <ul className="flex flex-col gap-2">
      {alerts.map((alert) => {
        const critical = alert.severity === "critical";
        const Icon = critical ? OctagonAlert : AlertTriangle;
        return (
          <li
            key={alert.id}
            className={cn("flex gap-3 rounded-lg p-3", critical ? "bg-negative/10" : "bg-warning/15")}
          >
            <Icon
              className={cn("mt-0.5 size-4 shrink-0", critical ? "text-negative" : "text-[oklch(0.5_0.12_70)]")}
              aria-hidden
            />
            <div className="min-w-0">
              <p className="text-sm font-medium">{alert.title}</p>
              <p className="truncate text-xs text-muted-foreground">{alert.description}</p>
            </div>
          </li>
        );
      })}
    </ul>
  );
}
