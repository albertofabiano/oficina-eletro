/** All business dates are ISO calendar days ("YYYY-MM-DD") in America/Sao_Paulo. */
export const TIME_ZONE = "America/Sao_Paulo";

export type IsoDate = string;

const ISO_DATE = /^\d{4}-\d{2}-\d{2}$/;

export function isIsoDate(value: string): value is IsoDate {
  return ISO_DATE.test(value);
}

export function todayInSaoPaulo(now: Date = new Date()): IsoDate {
  // en-CA formats as YYYY-MM-DD.
  return new Intl.DateTimeFormat("en-CA", {
    timeZone: TIME_ZONE,
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  }).format(now);
}

/** Calendar arithmetic on ISO days; done in UTC so DST never shifts the day. */
export function addDays(date: IsoDate, days: number): IsoDate {
  const [year, month, day] = date.split("-").map(Number) as [number, number, number];
  const shifted = new Date(Date.UTC(year, month - 1, day + days));
  return shifted.toISOString().slice(0, 10);
}

export function eachDay(from: IsoDate, to: IsoDate): IsoDate[] {
  const days: IsoDate[] = [];
  for (let current = from; current <= to; current = addDays(current, 1)) {
    days.push(current);
  }
  return days;
}

export function formatDayMonth(date: IsoDate): string {
  const [, month, day] = date.split("-");
  return `${day}/${month}`;
}
