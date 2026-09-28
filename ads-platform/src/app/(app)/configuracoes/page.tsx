import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { ConnectMetaForm } from "@/components/settings/connect-meta-form";
import { RemoveAccountButton } from "@/components/settings/remove-account-button";
import { canManageAccounts, getMembershipRole } from "@/lib/auth/membership";
import { requireOrganization } from "@/lib/auth/session";
import { loadEnv } from "@/lib/env";
import { formatRelativeTime } from "@/lib/format";
import { createClient } from "@/lib/supabase/server";
import { connectMetaAccount, removeAdAccount } from "./actions";

export const dynamic = "force-dynamic";

const PLATFORM_LABEL = { meta: "Meta Ads", fake: "Demonstração" } as const;

export default async function SettingsPage() {
  const organization = await requireOrganization();
  const membership = await getMembershipRole(organization.id);
  const canManage = canManageAccounts(membership?.role);
  const { DRY_RUN } = loadEnv();
  const supabase = await createClient();
  const { data: accounts, error } = await supabase
    .from("ad_accounts")
    .select("id, name, platform, external_id, status, last_synced_at, last_sync_error")
    .eq("organization_id", organization.id)
    .order("created_at");
  if (error) throw error;

  return (
    <div className="mx-auto flex max-w-4xl flex-col gap-6 p-4 md:p-8">
      <header className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-xl font-semibold">Configurações</h1>
          <p className="text-sm text-muted-foreground">Contas de anúncios de {organization.name}.</p>
        </div>
        {DRY_RUN && (
          <Badge variant="primary" title="Aprovações são registradas, mas nada é enviado às plataformas">
            Modo simulação
          </Badge>
        )}
      </header>

      <Card>
        <CardHeader>
          <CardTitle>Contas conectadas</CardTitle>
          <CardDescription>Os dados são coletados todos os dias às 6h e quando você clica em Sincronizar agora.</CardDescription>
        </CardHeader>
        <CardContent>
          {accounts.length === 0 ? (
            <p className="text-sm text-muted-foreground">Nenhuma conta conectada ainda.</p>
          ) : (
            <ul className="flex flex-col divide-y divide-border">
              {accounts.map((account) => {
                const disconnected = account.status === "disconnected";
                return (
                  <li key={account.id} className="flex flex-wrap items-start justify-between gap-3 py-3 first:pt-0 last:pb-0">
                    <div className="min-w-0">
                      <div className="flex flex-wrap items-center gap-2">
                        <span className="text-sm font-medium">{account.name}</span>
                        <Badge>{PLATFORM_LABEL[account.platform]}</Badge>
                        {disconnected ? (
                          <Badge>Desconectada</Badge>
                        ) : account.last_sync_error ? (
                          <Badge variant="negative">Com erro</Badge>
                        ) : (
                          <Badge variant="positive">Conectada</Badge>
                        )}
                      </div>
                      <p className="text-xs text-muted-foreground">
                        {account.platform === "meta" ? `${account.external_id} · ` : ""}
                        {account.last_synced_at
                          ? `última coleta ${formatRelativeTime(account.last_synced_at)}`
                          : "ainda não coletada"}
                      </p>
                      {!disconnected && account.last_sync_error && (
                        <p className="mt-1 text-xs text-negative">{account.last_sync_error}</p>
                      )}
                    </div>
                    {canManage && !disconnected && (
                      <RemoveAccountButton
                        accountId={account.id}
                        action={removeAdAccount}
                        label={account.platform === "fake" ? "Remover" : "Desconectar"}
                        confirmText={
                          account.platform === "fake"
                            ? "Remover a conta de demonstração e todos os dados simulados?"
                            : `Desconectar "${account.name}"? O token será apagado e a coleta para. O histórico continua no painel.`
                        }
                      />
                    )}
                  </li>
                );
              })}
            </ul>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Conectar conta da Meta</CardTitle>
          <CardDescription>
            A conta precisa estar em Real (BRL) e no fuso de São Paulo. Nada é alterado nas campanhas sem a sua
            aprovação{DRY_RUN ? ", e o modo simulação continua ligado" : ""}.
          </CardDescription>
        </CardHeader>
        <CardContent>
          {canManage ? (
            <ConnectMetaForm action={connectMetaAccount} />
          ) : (
            <p className="text-sm text-muted-foreground">
              Apenas o dono ou um administrador da empresa pode conectar contas.
            </p>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Como gerar o token</CardTitle>
          <CardDescription>Use um usuário do sistema: o token não expira e não depende da sua senha pessoal.</CardDescription>
        </CardHeader>
        <CardContent>
          <ol className="flex list-decimal flex-col gap-1.5 pl-5 text-sm">
            <li>
              Em <span className="font-medium">developers.facebook.com</span>, crie um app do tipo{" "}
              <span className="font-medium">Empresa</span> ligado ao seu Gerenciador de Negócios.
            </li>
            <li>
              No Gerenciador de Negócios, abra <span className="font-medium">Configurações do negócio → Usuários do sistema</span>{" "}
              e crie um usuário administrador.
            </li>
            <li>
              Em <span className="font-medium">Atribuir ativos</span>, dê a ele controle total da conta de anúncios.
            </li>
            <li>
              Clique em <span className="font-medium">Gerar novo token</span>, escolha o app, marque{" "}
              <span className="font-medium">ads_read</span> e <span className="font-medium">ads_management</span> e
              selecione validade <span className="font-medium">Nunca</span>.
            </li>
            <li>Copie o token e cole no formulário acima junto com o ID da conta.</li>
          </ol>
        </CardContent>
      </Card>
    </div>
  );
}
