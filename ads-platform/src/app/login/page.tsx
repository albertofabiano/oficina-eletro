import Link from "next/link";
import { AuthCard } from "@/components/auth/auth-card";
import { AuthForm } from "@/components/auth/auth-form";
import { safeNextPath } from "@/lib/auth/routes";
import { signIn, signUp } from "./actions";

const LINK_ERROR = "O link expirou ou é inválido. Entre novamente ou peça um novo link.";

export default async function LoginPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const params = await searchParams;
  const signingUp = params.modo === "cadastro";
  const next = safeNextPath(params.next);

  return (
    <AuthCard
      title={signingUp ? "Criar conta" : "Entrar"}
      description={signingUp ? "Cadastre-se para gerenciar seus anúncios." : "Acesse o painel de anúncios."}
    >
      {params.erro === "link" && (
        <p role="alert" className="mb-4 rounded-md bg-negative/10 px-3 py-2 text-sm text-negative">
          {LINK_ERROR}
        </p>
      )}
      {signingUp ? (
        <AuthForm action={signUp} submitLabel="Criar conta" pendingLabel="Criando…" passwordAutoComplete="new-password" />
      ) : (
        <AuthForm
          action={signIn}
          submitLabel="Entrar"
          pendingLabel="Entrando…"
          next={next}
          passwordAutoComplete="current-password"
        />
      )}
      <p className="mt-6 text-center text-sm text-muted-foreground">
        {signingUp ? "Já tem conta? " : "Ainda não tem conta? "}
        <Link href={signingUp ? "/login" : "/login?modo=cadastro"} className="font-medium text-primary">
          {signingUp ? "Entrar" : "Criar conta"}
        </Link>
      </p>
    </AuthCard>
  );
}
