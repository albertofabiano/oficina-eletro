import { AppSidebar, MobileHeader } from "@/components/app-sidebar";
import { getCurrentUser, requireOrganization } from "@/lib/auth/session";

export default async function DashboardLayout({ children }: { children: React.ReactNode }) {
  const organization = await requireOrganization();
  const user = await getCurrentUser();
  const account = { organizationName: organization.name, email: user?.email ?? "" };

  return (
    <div className="flex min-h-dvh">
      <AppSidebar account={account} />
      <main className="min-w-0 flex-1">
        <MobileHeader account={account} />
        {children}
      </main>
    </div>
  );
}
