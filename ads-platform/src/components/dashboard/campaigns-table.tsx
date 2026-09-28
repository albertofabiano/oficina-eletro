import Link from "next/link";
import { Badge } from "@/components/ui/badge";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import type { CampaignStatus } from "@/lib/ads/types";
import type { CampaignRow } from "@/lib/dashboard/metrics";
import { formatInteger, formatPercent } from "@/lib/format";
import { formatCents } from "@/lib/money";

const STATUS: Record<CampaignStatus, { label: string; variant: "positive" | "default" }> = {
  active: { label: "Ativa", variant: "positive" },
  paused: { label: "Pausada", variant: "default" },
  archived: { label: "Arquivada", variant: "default" },
};

const dash = "—";

export function CampaignsTable({ rows, linkToDetail = false }: { rows: CampaignRow[]; linkToDetail?: boolean }) {
  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>Campanha</TableHead>
          <TableHead>Status</TableHead>
          <TableHead className="text-right">Orçamento/dia</TableHead>
          <TableHead className="text-right">Investimento</TableHead>
          <TableHead className="text-right">Leads</TableHead>
          <TableHead className="text-right">Custo por lead</TableHead>
          <TableHead className="text-right">CTR</TableHead>
          <TableHead className="text-right">CPC</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {rows.map(({ campaign, metrics }) => (
          <TableRow key={campaign.id}>
            <TableCell className="font-medium">
              {linkToDetail ? (
                <Link href={`/campanhas/${campaign.id}`} className="text-primary underline-offset-2 hover:underline">
                  {campaign.name}
                </Link>
              ) : (
                campaign.name
              )}
            </TableCell>
            <TableCell>
              <Badge variant={STATUS[campaign.status].variant}>{STATUS[campaign.status].label}</Badge>
            </TableCell>
            <TableCell className="text-right">
              {campaign.dailyBudgetCents === null ? dash : formatCents(campaign.dailyBudgetCents)}
            </TableCell>
            <TableCell className="text-right">{formatCents(metrics.spendCents)}</TableCell>
            <TableCell className="text-right">{formatInteger(metrics.leads)}</TableCell>
            <TableCell className="text-right">
              {metrics.costPerLeadCents === null ? dash : formatCents(metrics.costPerLeadCents)}
            </TableCell>
            <TableCell className="text-right">{metrics.ctr === null ? dash : formatPercent(metrics.ctr)}</TableCell>
            <TableCell className="text-right">
              {metrics.costPerClickCents === null ? dash : formatCents(metrics.costPerClickCents)}
            </TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
}
