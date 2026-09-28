import type { ComponentProps } from "react";
import { cn } from "@/lib/utils";

const variants = {
  default: "bg-muted text-muted-foreground",
  positive: "bg-positive/10 text-positive",
  negative: "bg-negative/10 text-negative",
  warning: "bg-warning/15 text-[oklch(0.5_0.12_70)]",
  primary: "bg-primary/10 text-primary",
} as const;

export function Badge({
  className,
  variant = "default",
  ...props
}: ComponentProps<"span"> & { variant?: keyof typeof variants }) {
  return (
    <span
      className={cn(
        "inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap",
        variants[variant],
        className,
      )}
      {...props}
    />
  );
}
