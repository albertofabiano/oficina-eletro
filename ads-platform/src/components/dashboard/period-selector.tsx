import Link from "next/link";
import { PERIOD_OPTIONS, type PeriodDays } from "@/lib/dashboard/period";
import { cn } from "@/lib/utils";

export function PeriodSelector({
  value,
  basePath = "/dashboard",
  extraQuery,
}: {
  value: PeriodDays;
  basePath?: string;
  /** Other query parameters to keep, e.g. "status=ativas". */
  extraQuery?: string;
}) {
  return (
    <nav aria-label="Período" className="inline-flex rounded-lg border border-border bg-card p-0.5">
      {PERIOD_OPTIONS.map((days) => (
        <Link
          key={days}
          href={`${basePath}?${extraQuery ? `${extraQuery}&` : ""}periodo=${days}`}
          aria-current={days === value ? "page" : undefined}
          className={cn(
            "rounded-md px-3 py-1.5 text-xs font-medium whitespace-nowrap transition-colors",
            days === value ? "bg-primary text-primary-foreground" : "text-muted-foreground hover:text-foreground",
          )}
        >
          {days} dias
        </Link>
      ))}
    </nav>
  );
}
