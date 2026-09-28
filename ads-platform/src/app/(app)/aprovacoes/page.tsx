import { DecisionButtons } from "@/components/approvals/decision-buttons";
import { actionLabel, QueueStatusBadge } from "@/components/approvals/queue-status";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { getCurrentUser, requireOrganization } from "@/lib/auth/session";
import { loadEnv } from "@/lib/env";
import { formatRelativeTime } from "@/lib/format";
import { listQueue, type QueueItem } from "@/lib/queue/queries";
import { createClient } from "@/lib/supabase/server";
import { decideRequest, type DecisionOutcome } from "./actions";

export const dynamic = "force-dynamic";

const OUTCOMES: Record<DecisionOutcome, { text: string; tone: "positive" | "negative" | "muted" }> = {
  "aprovado-simulacao": { text: "Aprovado e registrado em modo simulação — nada foi enviado à Meta.", tone: "positive" },
  aprovado: { text: "Aprovado e aplicado na plataforma.", tone: "positive" },
  "aprovado-pendente": { text: "Aprovado. A execução será feita na próxima rotina.", tone: "muted" },
  falhou: { text: "Aprovado, mas a execução falhou. Veja o motivo no histórico.", tone: "negative" },
  rejeitado: { text: "Sugestão rejeitada. Ela não será sugerida de novo nos próximos 7 dias.", tone: "muted" },
  erro: { text: "Não foi possível registrar a decisão. Ela pode já ter sido tomada por outra pessoa.", tone: "negative" },
};

function OutcomeBanner({ outcome }: { outcome: unknown }) {
  if (typeof outcome !== "string" || !(outcome in OUTCOMES)) return null;
  const { text, tone } = OUTCOMES[outcome as DecisionOutcome];
  const styles = { positive: "bg-positive/10 text-positive", negative: "bg-negative/10 text-negative", muted: "bg-muted text-foreground" };
  return (
    <p role="status" className={`rounded-lg px-4 py-3 text-sm ${styles[tone]}`}>
      {text}
    </p>
  );
}


export default async function ApprovalsPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const { resultado } = await searchParams;
  const organization = await requireOrganization();
  const user = await getCurrentUser();
  const { DRY_RUN } = loadEnv();
  const { pending, history } = await listQueue(await createClient(), organization.id, user?.id ?? "");

  return (
    <div className="mx-auto flex max-w-4xl flex-col gap-6 p-4 md:p-8">
      <header className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-semibold">Aprovações</h1>
          <p className="text-sm text-muted-foreground">Nenhuma alteração é feita nas campanhas sem a sua aprovação.</p>
        </div>
        {DRY_RUN && (
          <Badge variant="primary" title="Aprovações são registradas, mas nada é enviado às plataformas">
            Modo simulação
          </Badge>
        )}
      </header>

      <OutcomeBanner outcome={resultado} />

      <Card>
        <CardHeader>
          <CardTitle>Sugestões pendentes</CardTitle>
          <CardDescription>Geradas pelas regras a partir dos últimos 7 dias. Atualizadas a cada sincronização.</CardDescription>
        </CardHeader>
        <CardContent>
          {pending.length === 0 ? (
            <p className="text-sm text-muted-foreground">Nenhuma sugestão aguardando decisão.</p>
          ) : (
            <ul className="flex flex-col divide-y divide-border">
              {pending.map((item) => (
                <li
                  key={item.id}
                  className="flex flex-col gap-3 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-start sm:justify-between"
                >
                  <div className="min-w-0">
                    <p className="text-sm font-medium">{actionLabel(item)}</p>
                    <p className="text-sm">{item.campaignName}</p>
                    <p className="mt-1 text-xs text-muted-foreground">{item.reason}</p>
                    <p className="mt-1 text-xs text-muted-foreground">Sugerida {formatRelativeTime(item.requestedAt)}</p>
                  </div>
                  <DecisionButtons requestId={item.id} action={decideRequest} />
                </li>
              ))}
            </ul>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Histórico</CardTitle>
          <CardDescription>Decisões registradas e o resultado da execução.</CardDescription>
        </CardHeader>
        <CardContent>
          {history.length === 0 ? (
            <p className="text-sm text-muted-foreground">Nenhuma decisão registrada ainda.</p>
          ) : (
            <ul className="flex flex-col divide-y divide-border">
              {history.map((item) => (
                <li key={item.id} className="flex flex-wrap items-start justify-between gap-2 py-3 first:pt-0 last:pb-0">
                  <div className="min-w-0">
                    <p className="text-sm">
                      <span className="font-medium">{actionLabel(item)}</span>
                      {" · "}
                      {item.campaignName}
                    </p>
                    <p className="text-xs text-muted-foreground">
                      {item.status === "rejected" ? "Rejeitada" : "Aprovada"}
                      {item.decidedByMe ? " por você" : " por outro membro"}
                      {item.decidedAt ? ` ${formatRelativeTime(item.decidedAt)}` : ""}
                      {item.error ? ` · ${item.error}` : ""}
                    </p>
                  </div>
                  <QueueStatusBadge item={item} />
                </li>
              ))}
            </ul>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
