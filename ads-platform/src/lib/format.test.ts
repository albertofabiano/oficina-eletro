import { describe, expect, it } from "vitest";
import { formatRelativeTime } from "./format";

const now = new Date("2026-09-28T12:00:00Z");

describe("formatRelativeTime", () => {
  it("describes recent times in Portuguese", () => {
    expect(formatRelativeTime("2026-09-28T11:55:00Z", now)).toBe("há 5 minutos");
    expect(formatRelativeTime("2026-09-28T09:00:00Z", now)).toBe("há 3 horas");
    expect(formatRelativeTime("2026-09-27T12:00:00Z", now)).toBe("ontem");
  });

  it("treats the last minute as now", () => {
    expect(formatRelativeTime("2026-09-28T11:59:40Z", now)).toBe("agora");
  });
});
