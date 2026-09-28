"use client";

import { Bar, CartesianGrid, ComposedChart, Line, ResponsiveContainer, Tooltip, XAxis, YAxis } from "recharts";
import type { DailyPoint } from "@/lib/dashboard/metrics";
import { formatDayMonth } from "@/lib/dates";
import { formatCents } from "@/lib/money";

export function SpendChart({ data }: { data: DailyPoint[] }) {
  return (
    <div className="h-72 w-full">
      <ResponsiveContainer width="100%" height="100%">
        <ComposedChart data={data} margin={{ top: 8, right: 8, bottom: 0, left: 0 }}>
          <CartesianGrid vertical={false} stroke="var(--color-border)" />
          <XAxis
            dataKey="date"
            tickFormatter={formatDayMonth}
            tickLine={false}
            axisLine={false}
            fontSize={12}
            stroke="var(--color-muted-foreground)"
            minTickGap={16}
          />
          <YAxis
            yAxisId="spend"
            tickFormatter={(cents: number) => formatCents(cents).replace(/,\d{2}$/, "")}
            tickLine={false}
            axisLine={false}
            fontSize={12}
            width={72}
            stroke="var(--color-muted-foreground)"
          />
          <YAxis
            yAxisId="leads"
            orientation="right"
            allowDecimals={false}
            tickLine={false}
            axisLine={false}
            fontSize={12}
            width={32}
            stroke="var(--color-muted-foreground)"
          />
          <Tooltip
            cursor={{ fill: "var(--color-muted)" }}
            labelFormatter={(label) => formatDayMonth(String(label))}
            formatter={(value, name) =>
              name === "Investimento" ? [formatCents(Number(value)), name] : [value, name]
            }
            contentStyle={{ borderRadius: 8, borderColor: "var(--color-border)", fontSize: 12 }}
          />
          <Bar
            yAxisId="spend"
            dataKey="spendCents"
            name="Investimento"
            fill="var(--color-chart-1)"
            radius={[4, 4, 0, 0]}
            maxBarSize={28}
            isAnimationActive={false}
          />
          <Line
            yAxisId="leads"
            dataKey="leads"
            name="Leads"
            type="monotone"
            stroke="var(--color-chart-2)"
            strokeWidth={2}
            dot={false}
            isAnimationActive={false}
          />
        </ComposedChart>
      </ResponsiveContainer>
    </div>
  );
}
