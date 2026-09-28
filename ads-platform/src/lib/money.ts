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

/**
 * Parses a decimal amount as sent by ad APIs ("12.34", "7", "0.5") into integer
 * cents without going through floating point. Extra decimals are rounded half up.
 */
export function decimalToCents(amount: string): Cents {
  const match = /^(\d+)(?:\.(\d+))?$/.exec(amount.trim());
  if (!match) throw new Error(`invalid amount: ${amount}`);
  const [, whole = "0", fraction = ""] = match;
  const padded = (fraction + "000").slice(0, 3);
  const cents = Number(whole) * 100 + Number(padded.slice(0, 2));
  return Number(padded[2]) >= 5 ? cents + 1 : cents;
}
