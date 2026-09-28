import { describe, expect, it } from "vitest";
import { divideCents, formatCents } from "./money";

describe("money", () => {
  it("formats cents as BRL", () => {
    expect(formatCents(123456).replace(/\s/g, " ")).toBe("R$ 1.234,56");
  });

  it("divides into integer cents", () => {
    expect(divideCents(1000, 3)).toBe(333);
    expect(divideCents(1001, 2)).toBe(501);
    expect(divideCents(1000, 0)).toBeNull();
  });
});
