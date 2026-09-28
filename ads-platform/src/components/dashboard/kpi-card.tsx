import { ArrowDownRight, ArrowUpRight } from "lucide-react";
import { Card, CardContent, CardHeader, CardDescription } from "@/components/ui/card";
import { formatChange } from "@/lib/format";
import { cn } from "@/lib/utils";

interface KpiCardProps {
  label: string;
  value: string;
  change: number | null;
  /** For cost metrics a rise is bad news. */
  higherIsBetter?: boolean;
}

export function KpiCard({ label, value, change, higherIsBetter = true }: KpiCardProps) {
  const good = change !== null && change !== 0 && change > 0 === higherIsBetter;
  const Arrow = change !== null && change < 0 ? ArrowDownRight : ArrowUpRight;

  return (
    <Card>
      <CardHeader>
        <CardDescription>{label}</CardDescription>
      </CardHeader>
      <CardContent className="flex flex-wrap items-end justify-between gap-x-2 gap-y-1">
        <span className="text-xl font-semibold tabular-nums sm:text-2xl">{value}</span>
        {change === null ? (
          <span className="text-xs text-muted-foreground">—</span>
        ) : (
          <span
            className={cn(
              "inline-flex items-center gap-0.5 text-xs font-medium tabular-nums",
              change === 0 ? "text-muted-foreground" : good ? "text-positive" : "text-negative",
            )}
            title="Comparado ao período anterior"
          >
            {change !== 0 && <Arrow className="size-3.5" aria-hidden />}
            {formatChange(change)}
          </span>
        )}
      </CardContent>
    </Card>
  );
}
