import { z } from "zod";

/**
 * Meta reports API usage per business in the `x-business-use-case-usage`
 * header, e.g. {"123":[{"type":"ads_insights","call_count":80,"total_cputime":20,
 * "total_time":35,"estimated_time_to_regain_access":0}]}. Values are percentages.
 */
const usageEntrySchema = z.object({
  call_count: z.number().optional(),
  total_cputime: z.number().optional(),
  total_time: z.number().optional(),
  estimated_time_to_regain_access: z.number().optional(),
});
const usageHeaderSchema = z.record(z.string(), z.array(usageEntrySchema));

export interface UsageSnapshot {
  /** Highest usage percentage across all reported buckets (0–100+). */
  percent: number;
  /** Minutes Meta says we are blocked for; 0 when not blocked. */
  regainAccessMinutes: number;
}

export function parseBusinessUseCaseUsage(header: string | null | undefined): UsageSnapshot | null {
  if (!header) return null;
  let json: unknown;
  try {
    json = JSON.parse(header);
  } catch {
    return null;
  }
  const parsed = usageHeaderSchema.safeParse(json);
  if (!parsed.success) return null;

  let percent = 0;
  let regainAccessMinutes = 0;
  for (const entries of Object.values(parsed.data)) {
    for (const entry of entries) {
      percent = Math.max(percent, entry.call_count ?? 0, entry.total_cputime ?? 0, entry.total_time ?? 0);
      regainAccessMinutes = Math.max(regainAccessMinutes, entry.estimated_time_to_regain_access ?? 0);
    }
  }
  return { percent, regainAccessMinutes };
}

export const SLOWDOWN_THRESHOLD_PERCENT = 75;

/**
 * How long to wait before the next call. Below 75% there is no delay; above it
 * the delay grows linearly up to `maxDelayMs` at 100%. When Meta reports a
 * block, wait the full time it asks for.
 */
export function delayForUsage(usage: UsageSnapshot | null, maxDelayMs = 60_000): number {
  if (!usage) return 0;
  if (usage.regainAccessMinutes > 0) return usage.regainAccessMinutes * 60_000;
  if (usage.percent <= SLOWDOWN_THRESHOLD_PERCENT) return 0;
  const ratio = Math.min(1, (usage.percent - SLOWDOWN_THRESHOLD_PERCENT) / (100 - SLOWDOWN_THRESHOLD_PERCENT));
  return Math.round(ratio * maxDelayMs);
}

/** Waits between API calls according to the last usage header seen. */
export class UsageThrottle {
  private nextDelayMs = 0;

  constructor(
    private readonly sleep: (ms: number) => Promise<void> = (ms) => new Promise((r) => setTimeout(r, ms)),
    private readonly maxDelayMs = 60_000,
  ) {}

  record(header: string | null | undefined): void {
    this.nextDelayMs = delayForUsage(parseBusinessUseCaseUsage(header), this.maxDelayMs);
  }

  async beforeRequest(): Promise<void> {
    if (this.nextDelayMs > 0) await this.sleep(this.nextDelayMs);
  }
}
