import { createServer, type Server } from "node:http";
import type { AddressInfo } from "node:net";
import { FacebookAdsApi } from "facebook-nodejs-business-sdk";
import { afterAll, beforeAll, describe, expect, it } from "vitest";
import { MetaApiError } from "./graph-client";
import { createSdkGraphClient, sanitize } from "./sdk-client";

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

describe("createSdkGraphClient against a Graph-like server", () => {
  // Real SDK and HTTP stack, pointed at a local server that answers like graph.facebook.com.
  const USAGE = '{"1":[{"type":"ads_management","call_count":5}]}';
  let server: Server;
  let restoreGraph: () => void;

  beforeAll(async () => {
    process.env.NO_PROXY = "127.0.0.1";
    server = createServer((req, res) => {
      const ok = req.url?.startsWith(`/${FacebookAdsApi.VERSION}/act_1?`);
      res.writeHead(ok ? 200 : 400, { "content-type": "application/json", "x-business-use-case-usage": USAGE });
      res.end(
        JSON.stringify(
          ok
            ? { id: "act_1", name: "Oficina" }
            : { error: { message: "Invalid OAuth access token", type: "OAuthException", code: 190, error_subcode: 463 } },
        ),
      );
    });
    await new Promise<void>((resolve) => server.listen(0, "127.0.0.1", resolve));
    const { port } = server.address() as AddressInfo;
    const original = Object.getOwnPropertyDescriptor(FacebookAdsApi, "GRAPH")!;
    Object.defineProperty(FacebookAdsApi, "GRAPH", { get: () => `http://127.0.0.1:${port}`, configurable: true });
    restoreGraph = () => Object.defineProperty(FacebookAdsApi, "GRAPH", original);
  });

  afterAll(() => {
    restoreGraph();
    server.close();
  });

  it("returns the body and the usage header", async () => {
    const client = createSdkGraphClient("EAASECRETTOKEN-aaaaaaaaaaaaaaa");
    expect(await client.get("act_1", { fields: "id,name" })).toEqual({
      body: { id: "act_1", name: "Oficina" },
      usageHeader: USAGE,
    });
  });

  it("keeps Meta's error code instead of reporting a network failure", async () => {
    const client = createSdkGraphClient("EAASECRETTOKEN-aaaaaaaaaaaaaaa");
    const error = await client.get("act_2", { fields: "id" }).catch((e: unknown) => e);
    expect(error).toBeInstanceOf(MetaApiError);
    expect(error).toMatchObject({ code: 190, subcode: 463, usageHeader: USAGE, message: "Invalid OAuth access token" });
    expect(JSON.stringify(error) + String(error)).not.toContain("EAASECRETTOKEN");
  });
});
