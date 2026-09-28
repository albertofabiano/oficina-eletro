import Link from "next/link";
import { notFound } from "next/navigation";
import { ArrowLeft } from "lucide-react";
import { z } from "zod";
import { actionLabel, QueueStatusBadge } from "@/components/approvals/queue-status";
import { KpiCard } from "@/components/dashboard/kpi-card";
import { PeriodSelector } from "@/components/dashboard/period-selector";
import { SpendChart } from "@/components/dashboard/spend-chart";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import type { CampaignStatus } from "@/lib/ads/types";
import { getCurrentUser, requireOrganization } from "@/lib/auth/session";
import { buildCampaignDetail } from "@/lib/campaigns/detail";
import { getCampaign, getCampaignInsights } from "@/lib/campaigns/queries";
import { percentChange } from "@/lib/dashboard/metrics";
import { parsePeriod, periodRanges } from "@/lib/dashboard/period";
import { formatDayMonth, todayInSaoPaulo } from "@/lib/dates";
import { formatInteger, formatPercent, formatRelativeTime } from "@/lib/format";
import { formatCents } from "@/lib/money";
import { listQueue } from "@/lib/queue/queries";
import { createClient } from "@/lib/supabase/server";

export const dynamic = "force-dynamic";

const STATUS: Record<CampaignStatus, { label: string; variant: "positive" | "default" }> = {
  active: { label: "Ativa", variant: "positive" },
  paused: { label: "Pausada", variant: "default" },
  archived: { label: "Arquivada", variant: "default" },
};

const dash = "—";

export default async function CampaignDetailPage({
  params,
  searchParams,
}: {
  params: Promise<{ id: string }>;
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const { id } = await params;
  if (!z.uuid().safeParse(id).success) notFound();
  const period = parsePeriod((await searchParams).periodo);

  const organization = await requireOrganization();
  const user = await getCurrentUser();
  const supabase = await createClient();
  const campaign = await getCampaign(supabase, organization.id, id);
  if (!campaign) notFound();

  const today = todayInSaoPaulo();
  const { current, previous } = periodRanges(period, today);
  const [insights, queue] = await Promise.all([
    getCampaignInsights(supabase, organization.id, id, { from: previous.from, to: current.to }),
    listQueue(supabase, organization.id, user?.id ?? "", { campaignId: id }),
  ]);
  const detail = buildCampaignDetail(insights, period, today);
  const { totals, previousTotals: prev } = detail;
  const requests = [...queue.pending, ...queue.history];
  const orDash = <T,>(value: T | null, format: (v: T) => string) => (value === null ? dash : format(value));

  return (
    <div className="mx-auto flex max-w-7xl flex-col gap-6 p-4 md:p-8">
      <Link
        href={`/campanhas?periodo=${period}`}
        className="inline-flex w-fit items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
      >
        <ArrowLeft className="size-4" aria-hidden />
        Campanhas
      </Link>

      <header className="flex flex-wrap items-start justify-between gap-3">
        <div className="min-w-0">
          <div className="flex flex-wrap items-center gap-2">
            <h1 className="text-xl font-semibold">{campaign.name}</h1>
            <Badge variant={STATUS[campaign.status].variant}>{STATUS[campaign.status].label}</Badge>
          </div>
          <p className="text-sm text-muted-foreground">
            Orçamento diário {orDash(campaign.dailyBudgetCents, formatCents)} · {campaign.accountName} · atualizada {formatRelativeTime(campaign.syncedAt)}
          </p>
          <p className="text-sm text-muted-foreground">
            {formatDayMonth(detail.range.from)} a {formatDayMonth(detail.range.to)}
          </p>
        </div>
        <PeriodSelector value={period} basePath={`/campanhas/${campaign.id}`} />
      </header>

      <section className="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Indicadores">
        <KpiCard
          label="Investimento"
          value={formatCents(totals.spendCents)}
          change={percentChange(totals.spendCents, prev.spendCents)}
          higherIsBetter={false}
        />
        <KpiCard label="Leads" value={formatInteger(totals.leads)} change={percentChange(totals.leads, prev.leads)} />
        <KpiCard
          label="Custo por lead"
          value={orDash(totals.costPerLeadCents, formatCents)}
          change={percentChange(totals.costPerLeadCents, prev.costPerLeadCents)}
          higherIsBetter={false}
        />
        <KpiCard label="CTR" value={orDash(totals.ctr, formatPercent)} change={percentChange(totals.ctr, prev.ctr)} />
      </section>

      <Card>
        <CardHeader>
          <CardTitle>Investimento x leads por dia</CardTitle>
          <CardDescription>Barras: investimento · Linha: leads</CardDescription>
        </CardHeader>
        <CardContent>
          <SpendChart data={detail.series} />
        </CardContent>
      </Card>

      <section className="grid grid-cols-1 gap-3 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>Dia a dia</CardTitle>
            <CardDescription>Os últimos 7 dias são recoletados a cada sincronização.</CardDescription>
          </CardHeader>
          <CardContent className="px-2">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Dia</TableHead>
                  <TableHead className="text-right">Investimento</TableHead>
                  <TableHead className="text-right">Impressões</TableHead>
                  <TableHead className="text-right">Cliques</TableHead>
                  <TableHead className="text-right">Leads</TableHead>
                  <TableHead className="text-right">Custo por lead</TableHead>
                  <TableHead className="text-right">CTR</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {detail.daily.map((day) => (
                  <TableRow key={day.date}>
                    <TableCell>{formatDayMonth(day.date)}</TableCell>
                    <TableCell className="text-right">{formatCents(day.spendCents)}</TableCell>
                    <TableCell className="text-right">{formatInteger(day.impressions)}</TableCell>
                    <TableCell className="text-right">{formatInteger(day.clicks)}</TableCell>
                    <TableCell className="text-right">{formatInteger(day.leads)}</TableCell>
                    <TableCell className="text-right">{orDash(day.costPerLeadCents, formatCents)}</TableCell>
                    <TableCell className="text-right">{orDash(day.ctr, formatPercent)}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Sugestões e alterações</CardTitle>
            <CardDescription>Tudo o que foi sugerido para esta campanha e o que foi decidido.</CardDescription>
          </CardHeader>
          <CardContent>
            {requests.length === 0 ? (
              <p className="text-sm text-muted-foreground">Nenhuma sugestão para esta campanha até agora.</p>
            ) : (
              <ul className="flex flex-col divide-y divide-border">
                {requests.map((item) => (
                  <li key={item.id} className="flex flex-col gap-1 py-3 first:pt-0 last:pb-0">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                      <span className="text-sm font-medium">{actionLabel(item)}</span>
                      <QueueStatusBadge item={item} />
                    </div>
                    <p className="text-xs text-muted-foreground">{item.reason}</p>
                    <p className="text-xs text-muted-foreground">
                      Sugerida {formatRelativeTime(item.requestedAt)}
                      {item.decidedAt
                        ? ` · ${item.status === "rejected" ? "rejeitada" : "aprovada"} ${item.decidedByMe ? "por você" : "por outro membro"} ${formatRelativeTime(item.decidedAt)}`
                        : ""}
                    </p>
                  </li>
                ))}
              </ul>
            )}
            {queue.pending.length > 0 && (
              <Link href="/aprovacoes" className="mt-3 inline-block text-sm font-medium text-primary hover:underline">
                Decidir na tela de Aprovações →
              </Link>
            )}
          </CardContent>
        </Card>
      </section>
    </div>
  );
}
