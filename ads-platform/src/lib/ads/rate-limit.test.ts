import { describe, expect, it, vi } from "vitest";
import { UsageThrottle, delayForUsage, parseBusinessUseCaseUsage } from "./rate-limit";

const header = (entries: object[]) => JSON.stringify({ "1234": entries });

describe("parseBusinessUseCaseUsage", () => {
  it("takes the highest percentage across buckets", () => {
    expect(
      parseBusinessUseCaseUsage(
        header([
          { type: "ads_insights", call_count: 40, total_cputime: 81, total_time: 12 },
          { type: "ads_management", call_count: 10, total_cputime: 5, total_time: 3 },
        ]),
      ),
    ).toEqual({ percent: 81, regainAccessMinutes: 0 });
  });

  it("ignores missing or malformed headers", () => {
    expect(parseBusinessUseCaseUsage(null)).toBeNull();
    expect(parseBusinessUseCaseUsage("not json")).toBeNull();
    expect(parseBusinessUseCaseUsage('{"1": "x"}')).toBeNull();
  });
});

describe("delayForUsage", () => {
  it("does not slow down at or below 75%", () => {
    expect(delayForUsage({ percent: 75, regainAccessMinutes: 0 })).toBe(0);
  });

  it("slows down proportionally above 75%", () => {
    expect(delayForUsage({ percent: 87.5, regainAccessMinutes: 0 }, 60_000)).toBe(30_000);
    expect(delayForUsage({ percent: 100, regainAccessMinutes: 0 }, 60_000)).toBe(60_000);
    expect(delayForUsage({ percent: 140, regainAccessMinutes: 0 }, 60_000)).toBe(60_000);
  });

  it("waits the full block time Meta asks for", () => {
    expect(delayForUsage({ percent: 100, regainAccessMinutes: 3 })).toBe(180_000);
  });
});

describe("UsageThrottle", () => {
  it("sleeps only after a high-usage response", async () => {
    const sleep = vi.fn(async () => {});
    const throttle = new UsageThrottle(sleep, 1000);
    await throttle.beforeRequest();
    throttle.record(header([{ call_count: 50 }]));
    await throttle.beforeRequest();
    expect(sleep).not.toHaveBeenCalled();
    throttle.record(header([{ call_count: 90 }]));
    await throttle.beforeRequest();
    expect(sleep).toHaveBeenCalledWith(600);
  });
});
