import { describe, expect, it } from "vitest";
import { loadEnv } from "./env";

describe("loadEnv", () => {
  it("defaults DRY_RUN to true (safe)", () => {
    expect(loadEnv({}).DRY_RUN).toBe(true);
  });

  it("parses explicit values", () => {
    expect(loadEnv({ DRY_RUN: "false" }).DRY_RUN).toBe(false);
  });

  it("rejects ambiguous values", () => {
    expect(() => loadEnv({ DRY_RUN: "yes" })).toThrow();
  });
});
