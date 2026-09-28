import Link from "next/link";
import { CampaignsTable } from "@/components/dashboard/campaigns-table";
import { PeriodSelector } from "@/components/dashboard/period-selector";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { requireOrganization } from "@/lib/auth/session";
import { matchesStatusFilter, parseStatusFilter, type StatusFilter } from "@/lib/campaigns/detail";
import { loadDashboard } from "@/lib/dashboard/load-dashboard";
import { parsePeriod } from "@/lib/dashboard/period";
import { SupabaseDashboardDataSource } from "@/lib/dashboard/supabase-data-source";
import { formatDayMonth, todayInSaoPaulo } from "@/lib/dates";
import { createClient } from "@/lib/supabase/server";
import { cn } from "@/lib/utils";

export const dynamic = "force-dynamic";

const FILTERS: Array<{ value: StatusFilter; label: string }> = [
  { value: "todas", label: "Todas" },
  { value: "ativas", label: "Ativas" },
  { value: "pausadas", label: "Pausadas" },
];

export default async function CampaignsPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const params = await searchParams;
  const period = parsePeriod(params.periodo);
  const status = parseStatusFilter(params.status);
  const organization = await requireOrganization();
  const supabase = await createClient();

  const data = await loadDashboard(new SupabaseDashboardDataSource(supabase, organization.id), period, todayInSaoPaulo());
  const rows = data.rows.filter((row) => matchesStatusFilter(row.campaign.status, status));
  const counts = Object.fromEntries(
    FILTERS.map((f) => [f.value, data.rows.filter((row) => matchesStatusFilter(row.campaign.status, f.value)).length]),
  ) as Record<StatusFilter, number>;

  return (
    <div className="mx-auto flex max-w-7xl flex-col gap-6 p-4 md:p-8">
      <header className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-semibold">Campanhas</h1>
          <p className="text-sm text-muted-foreground">
            {formatDayMonth(data.range.from)} a {formatDayMonth(data.range.to)} · clique em uma campanha para ver os detalhes
          </p>
        </div>
        <PeriodSelector value={period} basePath="/campanhas" extraQuery={`status=${status}`} />
      </header>

      <nav aria-label="Filtrar por status" className="flex flex-wrap gap-2">
        {FILTERS.map((f) => (
          <Link
            key={f.value}
            href={`/campanhas?status=${f.value}&periodo=${period}`}
            aria-current={f.value === status ? "page" : undefined}
            className={cn(
              "rounded-full border px-3 py-1 text-xs font-medium",
              f.value === status
                ? "border-primary bg-primary text-primary-foreground"
                : "border-border bg-card text-muted-foreground hover:text-foreground",
            )}
          >
            {f.label} ({counts[f.value]})
          </Link>
        ))}
      </nav>

      <Card>
        <CardHeader>
          <CardTitle>{FILTERS.find((f) => f.value === status)?.label}</CardTitle>
          <CardDescription>Ordenadas por investimento no período.</CardDescription>
        </CardHeader>
        <CardContent className="px-2">
          {rows.length === 0 ? (
            <p className="px-3 text-sm text-muted-foreground">
              {data.rows.length === 0
                ? "Nenhuma campanha ainda. Conecte uma conta de anúncios no Painel."
                : "Nenhuma campanha com esse status."}
            </p>
          ) : (
            <CampaignsTable rows={rows} linkToDetail />
          )}
        </CardContent>
      </Card>
    </div>
  );
}
