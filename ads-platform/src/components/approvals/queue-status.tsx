import { Badge } from "@/components/ui/badge";
import { describeAction } from "@/lib/optimization/describe";
import type { QueueItem } from "@/lib/queue/queries";

type DecidedStatus = Exclude<QueueItem["status"], "pending">;

const STATUS: Record<DecidedStatus, { label: string; variant: "positive" | "negative" | "default" | "primary" }> = {
  approved: { label: "Aprovada", variant: "primary" },
  rejected: { label: "Rejeitada", variant: "default" },
  executed: { label: "Executada", variant: "positive" },
  failed: { label: "Falhou", variant: "negative" },
};

export function QueueStatusBadge({ item }: { item: QueueItem }) {
  if (item.status === "pending") return <Badge variant="warning">Aguardando aprovação</Badge>;
  const status = STATUS[item.status];
  const label = item.status === "executed" && item.dryRun ? "Executada (simulação)" : status.label;
  return <Badge variant={status.variant}>{label}</Badge>;
}

export function actionLabel(item: QueueItem) {
  return item.action ? describeAction(item.action) : "Ação inválida";
}
