import { AppSidebar, MobileHeader } from "@/components/app-sidebar";

export default function DashboardLayout({ children }: { children: React.ReactNode }) {
  return (
    <div className="flex min-h-dvh">
      <AppSidebar />
      <main className="min-w-0 flex-1">
        <MobileHeader />
        {children}
      </main>
    </div>
  );
}
