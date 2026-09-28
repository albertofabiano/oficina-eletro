import { z } from "zod";
import { addDays, type IsoDate } from "@/lib/dates";

export const PERIOD_OPTIONS = [7, 14, 30] as const;
export type PeriodDays = (typeof PERIOD_OPTIONS)[number];

const periodSchema = z.coerce
  .number()
  .int()
  .refine((value): value is PeriodDays => (PERIOD_OPTIONS as readonly number[]).includes(value));

export function parsePeriod(raw: unknown, fallback: PeriodDays = 7): PeriodDays {
  const result = periodSchema.safeParse(Array.isArray(raw) ? raw[0] : raw);
  return result.success ? (result.data as PeriodDays) : fallback;
}

export interface DateRange {
  from: IsoDate;
  to: IsoDate;
}

/** Period ending yesterday (today is still incomplete) and the equally long period before it. */
export function periodRanges(days: PeriodDays, today: IsoDate): { current: DateRange; previous: DateRange } {
  const to = addDays(today, -1);
  const from = addDays(to, -(days - 1));
  return {
    current: { from, to },
    previous: { from: addDays(from, -days), to: addDays(from, -1) },
  };
}
