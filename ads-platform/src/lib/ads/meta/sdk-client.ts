import { FacebookAdsApi } from "facebook-nodejs-business-sdk";
import { z } from "zod";
import { MetaApiError, type GraphResponse, type MetaGraphClient } from "./graph-client";

const USAGE_HEADER = "x-business-use-case-usage";

// Only Meta's error body is validated: the SDK's `headers` is an AxiosHeaders
// instance, which a plain-object schema would reject and hide the real error.
const sdkErrorSchema = z.object({
  response: z
    .object({
      message: z.string().optional(),
      code: z.number().optional(),
      error_subcode: z.number().optional(),
      error_user_msg: z.string().optional(),
    })
    .nullish(),
});

/** Reads the usage header from a plain object or an AxiosHeaders instance. */
function usageFrom(headers: unknown): string | null {
  if (!headers || typeof headers !== "object") return null;
  const value = (headers as Record<string, unknown>)[USAGE_HEADER];
  return typeof value === "string" ? value : null;
}

/**
 * The SDK's FacebookRequestError keeps the request URL and data, which include
 * the access token. Rebuild it as a MetaApiError with only Meta's error fields.
 */
export function sanitize(error: unknown): MetaApiError {
  const parsed = sdkErrorSchema.safeParse(error);
  if (!parsed.success || !parsed.data.response) {
    return new MetaApiError("Falha de comunicação com a API da Meta.", null, null);
  }
  const { response } = parsed.data;
  const headers = (error as { headers?: unknown }).headers;
  return new MetaApiError(
    response.error_user_msg ?? response.message ?? "Erro desconhecido da API da Meta.",
    response.code ?? null,
    response.error_subcode ?? null,
    usageFrom(headers),
  );
}

/** MetaGraphClient backed by the official Meta Business SDK. */
export function createSdkGraphClient(accessToken: string): MetaGraphClient {
  // crashLog=false: the SDK's crash reporter hooks global error handlers and reports to Meta.
  const api = new FacebookAdsApi(accessToken, "pt_BR", false).setShowHeader(true);

  async function call(method: "GET" | "POST", path: string | string[], params: Record<string, string> = {}) {
    try {
      const { headers, ...body } = await api.call(method, path, params);
      return { body, usageHeader: usageFrom(headers) } satisfies GraphResponse;
    } catch (error) {
      throw sanitize(error);
    }
  }

  return {
    get: (path, params) => call("GET", path.split("/"), params),
    post: (path, params) => call("POST", path.split("/"), params),
    getNext: (url) => {
      // Only follow Graph API URLs: the token is attached to every request.
      if (!url.startsWith("https://graph.facebook.com/")) throw new MetaApiError("URL de paginação inesperada.", null, null);
      return call("GET", url);
    },
  };
}
