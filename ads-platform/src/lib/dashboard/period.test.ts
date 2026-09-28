import { describe, expect, it } from "vitest";
import { parsePeriod, periodRanges } from "./period";

describe("parsePeriod", () => {
  it("accepts allowed values", () => {
    expect(parsePeriod("14")).toBe(14);
    expect(parsePeriod(["30"])).toBe(30);
  });

  it("falls back on invalid input", () => {
    expect(parsePeriod("15")).toBe(7);
    expect(parsePeriod(undefined)).toBe(7);
    expect(parsePeriod("abc", 30)).toBe(30);
  });
});

describe("periodRanges", () => {
  it("ends yesterday and compares with the previous window", () => {
    expect(periodRanges(7, "2026-09-28")).toEqual({
      current: { from: "2026-09-21", to: "2026-09-27" },
      previous: { from: "2026-09-14", to: "2026-09-20" },
    });
  });
});
