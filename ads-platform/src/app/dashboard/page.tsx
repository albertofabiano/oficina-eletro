import { AlertsPanel } from "@/components/dashboard/alerts-panel";
import { CampaignsTable } from "@/components/dashboard/campaigns-table";
import { KpiCard } from "@/components/dashboard/kpi-card";
import { PeriodSelector } from "@/components/dashboard/period-selector";
import { SpendChart } from "@/components/dashboard/spend-chart";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { loadDashboard } from "@/lib/dashboard/load-dashboard";
import { MockDashboardDataSource } from "@/lib/dashboard/mock-data-source";
import { percentChange } from "@/lib/dashboard/metrics";
import { parsePeriod } from "@/lib/dashboard/period";
import { formatDayMonth, todayInSaoPaulo } from "@/lib/dates";
import { loadEnv } from "@/lib/env";
import { formatInteger, formatPercent } from "@/lib/format";
import { formatCents } from "@/lib/money";

export const dynamic = "force-dynamic";

export default async function DashboardPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const period = parsePeriod((await searchParams).periodo);
  const { DRY_RUN } = loadEnv();
  const data = await loadDashboard(new MockDashboardDataSource(), period, todayInSaoPaulo());
  const { totals, previousTotals: prev } = data;
  const orDash = <T,>(value: T | null, format: (v: T) => string) => (value === null ? "—" : format(value));

  return (
    <div className="mx-auto flex max-w-7xl flex-col gap-6 p-4 md:p-8">
      <header className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-semibold">Painel</h1>
          <p className="text-sm text-muted-foreground">
            Meta Ads · {formatDayMonth(data.range.from)} a {formatDayMonth(data.range.to)}
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <Badge variant="warning" title="Dados fictícios até a integração com a Meta">
            Dados de exemplo
          </Badge>
          {DRY_RUN && (
            <Badge variant="primary" title="Nenhuma alteração é enviada às plataformas">
              Modo simulação
            </Badge>
          )}
          <PeriodSelector value={period} />
        </div>
      </header>

      <section className="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Indicadores">
        <KpiCard
          label="Investimento"
          value={formatCents(totals.spendCents)}
          change={percentChange(totals.spendCents, prev.spendCents)}
          higherIsBetter={false}
        />
        <KpiCard
          label="Leads"
          value={formatInteger(totals.leads)}
          change={percentChange(totals.leads, prev.leads)}
        />
        <KpiCard
          label="Custo por lead"
          value={orDash(totals.costPerLeadCents, formatCents)}
          change={percentChange(totals.costPerLeadCents, prev.costPerLeadCents)}
          higherIsBetter={false}
        />
        <KpiCard
          label="CTR"
          value={orDash(totals.ctr, formatPercent)}
          change={percentChange(totals.ctr, prev.ctr)}
        />
        <KpiCard
          label="Impressões"
          value={formatInteger(totals.impressions)}
          change={percentChange(totals.impressions, prev.impressions)}
        />
        <KpiCard
          label="Cliques"
          value={formatInteger(totals.clicks)}
          change={percentChange(totals.clicks, prev.clicks)}
        />
        <KpiCard
          label="CPC"
          value={orDash(totals.costPerClickCents, formatCents)}
          change={percentChange(totals.costPerClickCents, prev.costPerClickCents)}
          higherIsBetter={false}
        />
        <KpiCard
          label="CPM"
          value={orDash(totals.cpmCents, formatCents)}
          change={percentChange(totals.cpmCents, prev.cpmCents)}
          higherIsBetter={false}
        />
      </section>

      <section className="grid gap-3 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>Investimento x leads por dia</CardTitle>
            <CardDescription>Barras: investimento · Linha: leads</CardDescription>
          </CardHeader>
          <CardContent>
            <SpendChart data={data.series} />
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>Alertas</CardTitle>
            <CardDescription>Sugestões só são aplicadas após sua aprovação.</CardDescription>
          </CardHeader>
          <CardContent>
            <AlertsPanel alerts={data.alerts} />
          </CardContent>
        </Card>
      </section>

      <Card>
        <CardHeader>
          <CardTitle>Campanhas</CardTitle>
          <CardDescription>Ordenadas por investimento no período.</CardDescription>
        </CardHeader>
        <CardContent className="px-2">
          <CampaignsTable rows={data.rows} />
        </CardContent>
      </Card>
    </div>
  );
}
