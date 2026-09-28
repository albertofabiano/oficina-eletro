import { describe, expect, it } from "vitest";
import { credentialsSchema, firstIssue, organizationNameSchema } from "./credentials";

describe("credentialsSchema", () => {
  it("normalizes the e-mail", () => {
    expect(credentialsSchema.parse({ email: " Ana@Example.com ", password: "12345678" }).email).toBe(
      "ana@example.com",
    );
  });

  it("rejects short passwords with a Portuguese message", () => {
    const result = credentialsSchema.safeParse({ email: "ana@example.com", password: "123" });
    expect(result.success).toBe(false);
    if (!result.success) expect(firstIssue(result.error)).toMatch(/8 caracteres/);
  });
});

describe("organizationNameSchema", () => {
  it("trims and validates", () => {
    expect(organizationNameSchema.parse("  Oficina  ")).toBe("Oficina");
    expect(organizationNameSchema.safeParse(" a ").success).toBe(false);
  });
});
