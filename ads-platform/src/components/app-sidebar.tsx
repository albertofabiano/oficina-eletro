import Link from "next/link";
import { BarChart3, CheckSquare, LogOut, Megaphone, Settings } from "lucide-react";
import { signOut } from "@/lib/auth/actions";

const NAV = [
  { href: "/dashboard", label: "Painel", icon: BarChart3, ready: true },
  { href: "#", label: "Campanhas", icon: Megaphone, ready: false },
  { href: "#", label: "Aprovações", icon: CheckSquare, ready: false },
  { href: "#", label: "Configurações", icon: Settings, ready: false },
] as const;

export interface SidebarAccount {
  organizationName: string;
  email: string;
}

function Brand() {
  return (
    <>
      <span className="grid size-7 place-items-center rounded-md bg-primary text-xs text-primary-foreground">TP</span>
      Tráfego Pago
    </>
  );
}

function SignOutButton({ compact = false }: { compact?: boolean }) {
  return (
    <form action={signOut}>
      <button
        type="submit"
        className="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-muted-foreground hover:bg-muted hover:text-foreground"
        title="Sair"
      >
        <LogOut className="size-4" aria-hidden />
        {compact ? <span className="sr-only">Sair</span> : "Sair"}
      </button>
    </form>
  );
}

export function AppSidebar({ account }: { account: SidebarAccount }) {
  return (
    <aside className="sticky top-0 hidden h-dvh w-60 shrink-0 flex-col border-r border-border bg-card md:flex">
      <div className="flex h-14 items-center gap-2 px-5 font-semibold">
        <Brand />
      </div>
      <nav className="flex flex-col gap-0.5 p-3">
        {NAV.map(({ href, label, icon: Icon, ready }) =>
          ready ? (
            <Link
              key={label}
              href={href}
              className="flex items-center gap-2 rounded-md bg-muted px-3 py-2 text-sm font-medium"
            >
              <Icon className="size-4" aria-hidden />
              {label}
            </Link>
          ) : (
            <span
              key={label}
              className="flex cursor-not-allowed items-center gap-2 rounded-md px-3 py-2 text-sm text-muted-foreground"
              title="Em breve"
            >
              <Icon className="size-4" aria-hidden />
              {label}
              <span className="ml-auto text-[10px] whitespace-nowrap uppercase">em breve</span>
            </span>
          ),
        )}
      </nav>
      <div className="mt-auto border-t border-border p-3">
        <div className="px-3 py-2">
          <p className="truncate text-sm font-medium">{account.organizationName}</p>
          <p className="truncate text-xs text-muted-foreground">{account.email}</p>
        </div>
        <SignOutButton />
      </div>
    </aside>
  );
}

export function MobileHeader({ account }: { account: SidebarAccount }) {
  return (
    <header className="flex h-14 items-center gap-2 border-b border-border bg-card px-4 font-semibold md:hidden">
      <Brand />
      <span className="ml-auto max-w-32 truncate text-xs font-normal text-muted-foreground">
        {account.organizationName}
      </span>
      <SignOutButton compact />
    </header>
  );
}
