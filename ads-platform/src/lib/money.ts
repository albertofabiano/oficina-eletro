/** Money is always integer cents. Floats only appear at the display boundary. */
export type Cents = number;

const brl = new Intl.NumberFormat("pt-BR", { style: "currency", currency: "BRL" });

export function formatCents(cents: Cents): string {
  return brl.format(cents / 100);
}

/** Integer division of an amount in cents, rounded half up; null when dividing by zero. */
export function divideCents(cents: Cents, divisor: number): Cents | null {
  if (divisor === 0) return null;
  return Math.round(cents / divisor);
}
