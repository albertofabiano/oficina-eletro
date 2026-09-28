import { describe, expect, it } from "vitest";
import { decimalToCents, divideCents, formatCents } from "./money";

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

describe("decimalToCents", () => {
  it("parses decimal strings exactly", () => {
    expect(decimalToCents("12.34")).toBe(1234);
    expect(decimalToCents("7")).toBe(700);
    expect(decimalToCents("0.5")).toBe(50);
    expect(decimalToCents("0.1")).toBe(10);
    expect(decimalToCents("19.999")).toBe(2000);
    expect(decimalToCents("19.994")).toBe(1999);
  });

  it("rejects invalid input", () => {
    expect(() => decimalToCents("-1")).toThrow();
    expect(() => decimalToCents("1,50")).toThrow();
    expect(() => decimalToCents("")).toThrow();
  });
});
