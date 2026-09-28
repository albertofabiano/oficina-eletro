import { describe, expect, it } from "vitest";
import { sanitize } from "./sdk-client";

describe("sanitize", () => {
  it("keeps Meta's error fields and drops the URL and data that carry the token", () => {
    // Shape of the SDK's FacebookRequestError.
    const sdkError = Object.assign(new Error("Error validating access token"), {
      name: "FacebookRequestError",
      status: 400,
      response: { message: "Error validating access token", code: 190, error_subcode: 463, type: "OAuthException" },
      headers: { "x-business-use-case-usage": '{"1":[{"call_count":10}]}' },
      method: "GET",
      url: "https://graph.facebook.com/v24.0/act_1/campaigns?access_token=EAASECRETTOKEN",
      data: { access_token: "EAASECRETTOKEN" },
    });

    const error = sanitize(sdkError);
    expect(error).toMatchObject({ code: 190, subcode: 463, usageHeader: '{"1":[{"call_count":10}]}' });
    expect(JSON.stringify(error) + String(error) + (error.stack ?? "")).not.toContain("EAASECRETTOKEN");
  });

  it("hides network errors behind a generic message", () => {
    const error = sanitize(new Error("connect ECONNREFUSED https://graph.facebook.com/?access_token=EAASECRETTOKEN"));
    expect(error.message).toBe("Falha de comunicação com a API da Meta.");
    expect(error.code).toBeNull();
  });
});
