import Link from "next/link";
import { BarChart3, CheckSquare, Megaphone, Settings } from "lucide-react";

const NAV = [
  { href: "/dashboard", label: "Painel", icon: BarChart3, ready: true },
  { href: "#", label: "Campanhas", icon: Megaphone, ready: false },
  { href: "#", label: "Aprovações", icon: CheckSquare, ready: false },
  { href: "#", label: "Configurações", icon: Settings, ready: false },
] as const;

export function AppSidebar() {
  return (
    <aside className="hidden w-60 shrink-0 flex-col border-r border-border bg-card md:flex">
      <div className="flex h-14 items-center gap-2 px-5 font-semibold">
        <span className="grid size-7 place-items-center rounded-md bg-primary text-xs text-primary-foreground">TP</span>
        Tráfego Pago
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
    </aside>
  );
}

export function MobileHeader() {
  return (
    <header className="flex h-14 items-center gap-2 border-b border-border bg-card px-4 font-semibold md:hidden">
      <span className="grid size-7 place-items-center rounded-md bg-primary text-xs text-primary-foreground">TP</span>
      Tráfego Pago
    </header>
  );
}
