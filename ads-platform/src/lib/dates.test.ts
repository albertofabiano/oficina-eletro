import { describe, expect, it } from "vitest";
import { addDays, eachDay, formatDayMonth, todayInSaoPaulo } from "./dates";

describe("todayInSaoPaulo", () => {
  it("uses the São Paulo calendar day, not UTC", () => {
    // 02:00 UTC on Mar 10 is still 23:00 on Mar 9 in São Paulo (UTC-3).
    expect(todayInSaoPaulo(new Date("2026-03-10T02:00:00Z"))).toBe("2026-03-09");
    expect(todayInSaoPaulo(new Date("2026-03-10T03:00:00Z"))).toBe("2026-03-10");
  });
});

describe("addDays / eachDay", () => {
  it("crosses month and year boundaries", () => {
    expect(addDays("2026-12-31", 1)).toBe("2027-01-01");
    expect(addDays("2026-03-01", -1)).toBe("2026-02-28");
  });

  it("lists every day inclusive", () => {
    expect(eachDay("2026-01-30", "2026-02-02")).toEqual([
      "2026-01-30",
      "2026-01-31",
      "2026-02-01",
      "2026-02-02",
    ]);
  });

  it("formats as dd/mm", () => {
    expect(formatDayMonth("2026-09-07")).toBe("07/09");
  });
});
