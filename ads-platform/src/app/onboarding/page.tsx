import { redirect } from "next/navigation";
import { AuthCard } from "@/components/auth/auth-card";
import { OrganizationForm } from "@/components/auth/organization-form";
import { getCurrentOrganization, requireUser } from "@/lib/auth/session";
import { createOrganization } from "./actions";

export default async function OnboardingPage() {
  await requireUser();
  if (await getCurrentOrganization()) redirect("/dashboard");

  return (
    <AuthCard title="Sua empresa" description="Informe o nome do negócio cujos anúncios você vai gerenciar.">
      <OrganizationForm action={createOrganization} />
    </AuthCard>
  );
}
