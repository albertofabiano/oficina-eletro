import { MobileNav } from "@/components/app-nav";
import { AppSidebar, MobileHeader } from "@/components/app-sidebar";
import { getCurrentUser, requireOrganization } from "@/lib/auth/session";
import { countPending } from "@/lib/queue/queries";
import { createClient } from "@/lib/supabase/server";

export default async function AppLayout({ children }: { children: React.ReactNode }) {
  const organization = await requireOrganization();
  const user = await getCurrentUser();
  const pendingApprovals = await countPending(await createClient(), organization.id);
  const account = { organizationName: organization.name, email: user?.email ?? "" };

  return (
    <div className="flex min-h-dvh">
      <AppSidebar account={account} pendingApprovals={pendingApprovals} />
      <main className="min-w-0 flex-1">
        <MobileHeader account={account} />
        <MobileNav pendingApprovals={pendingApprovals} />
        {children}
      </main>
    </div>
  );
}
