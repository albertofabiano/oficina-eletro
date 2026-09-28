import { describe, expect, it } from "vitest";
import { checkAccountRequirements, connectMetaSchema } from "./connect";

const info = { externalId: "act_123456789", name: "Oficina", currency: "BRL", timezone: "America/Sao_Paulo", accountStatus: 1 };

describe("connectMetaSchema", () => {
  it("normalizes the account id and trims the token", () => {
    expect(connectMetaSchema.parse({ adAccountId: "123456789", accessToken: "  EAAB1234567890abcdefghij  " })).toEqual({
      adAccountId: "act_123456789",
      accessToken: "EAAB1234567890abcdefghij",
    });
  });

  it("rejects bad ids and pasted text around the token", () => {
    expect(connectMetaSchema.safeParse({ adAccountId: "minha conta", accessToken: "EAAB1234567890abcdefghij" }).success).toBe(false);
    expect(connectMetaSchema.safeParse({ adAccountId: "123456789", accessToken: "token: EAAB1234567890abcdefghij" }).success).toBe(false);
    expect(connectMetaSchema.safeParse({ adAccountId: "123456789", accessToken: "curto" }).success).toBe(false);
  });
});

describe("checkAccountRequirements", () => {
  it("accepts an active BRL account in São Paulo time", () => {
    expect(checkAccountRequirements(info)).toBeNull();
    expect(checkAccountRequirements({ ...info, accountStatus: 9 })).toBeNull();
  });

  it("rejects other currencies, other timezones and closed accounts", () => {
    expect(checkAccountRequirements({ ...info, currency: "USD" })).toContain("BRL");
    expect(checkAccountRequirements({ ...info, timezone: "America/Los_Angeles" })).toContain("America/Sao_Paulo");
    expect(checkAccountRequirements({ ...info, accountStatus: 2 })).toContain("desativada");
    expect(checkAccountRequirements({ ...info, accountStatus: 101 })).toContain("encerrada");
  });
});
