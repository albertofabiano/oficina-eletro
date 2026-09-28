import { describe, expect, it } from "vitest";
import { isPublicPath, safeNextPath } from "./routes";

describe("isPublicPath", () => {
  it("allows login and auth callbacks", () => {
    expect(isPublicPath("/login")).toBe(true);
    expect(isPublicPath("/auth/confirm")).toBe(true);
  });

  it("protects the app", () => {
    expect(isPublicPath("/dashboard")).toBe(false);
    expect(isPublicPath("/")).toBe(false);
    expect(isPublicPath("/login-extra")).toBe(false);
  });
});

describe("safeNextPath", () => {
  it("keeps relative paths", () => {
    expect(safeNextPath("/dashboard?periodo=14")).toBe("/dashboard?periodo=14");
  });

  it("rejects external or malformed targets", () => {
    expect(safeNextPath("https://evil.com")).toBe("/dashboard");
    expect(safeNextPath("//evil.com")).toBe("/dashboard");
    expect(safeNextPath("/\\evil.com")).toBe("/dashboard");
    expect(safeNextPath(undefined)).toBe("/dashboard");
  });
});
