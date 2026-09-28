"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { BarChart3, CheckSquare, Megaphone, Settings } from "lucide-react";
import { cn } from "@/lib/utils";

const NAV = [
  { href: "/dashboard", label: "Painel", icon: BarChart3, ready: true },
  { href: "/campanhas", label: "Campanhas", icon: Megaphone, ready: true },
  { href: "/aprovacoes", label: "Aprovações", icon: CheckSquare, ready: true },
  { href: "#", label: "Configurações", icon: Settings, ready: false },
] as const;

export function AppNav({ pendingApprovals }: { pendingApprovals: number }) {
  const pathname = usePathname();

  return (
    <nav className="flex flex-col gap-0.5 p-3">
      {NAV.map(({ href, label, icon: Icon, ready }) =>
        ready ? (
          <Link
            key={label}
            href={href}
            aria-current={pathname.startsWith(href) ? "page" : undefined}
            className={cn(
              "flex items-center gap-2 rounded-md px-3 py-2 text-sm",
              pathname.startsWith(href) ? "bg-muted font-medium" : "text-muted-foreground hover:bg-muted hover:text-foreground",
            )}
          >
            <Icon className="size-4" aria-hidden />
            {label}
            {href === "/aprovacoes" && pendingApprovals > 0 && (
              <span className="ml-auto rounded-full bg-primary px-1.5 text-[11px] font-semibold text-primary-foreground tabular-nums">
                {pendingApprovals}
              </span>
            )}
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
  );
}

/** Compact navigation for small screens. */
export function MobileNav({ pendingApprovals }: { pendingApprovals: number }) {
  const pathname = usePathname();
  return (
    <nav className="flex gap-1 border-b border-border bg-card px-2 py-1.5 md:hidden">
      {NAV.filter((item) => item.ready).map(({ href, label, icon: Icon }) => (
        <Link
          key={label}
          href={href}
          aria-current={pathname.startsWith(href) ? "page" : undefined}
          className={cn(
            "flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs",
            pathname.startsWith(href) ? "bg-muted font-medium" : "text-muted-foreground",
          )}
        >
          <Icon className="size-3.5" aria-hidden />
          {label}
          {href === "/aprovacoes" && pendingApprovals > 0 && (
            <span className="rounded-full bg-primary px-1.5 text-[10px] font-semibold text-primary-foreground">
              {pendingApprovals}
            </span>
          )}
        </Link>
      ))}
    </nav>
  );
}
