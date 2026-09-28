/** One Graph API response: the JSON body plus the usage header that drives the throttle. */
export interface GraphResponse {
  body: unknown;
  usageHeader: string | null;
}

/**
 * The slice of the Graph API used by MetaAdsPlatform. Production uses the Meta
 * Business SDK (sdk-client.ts); tests use a fake with canned responses.
 */
export interface MetaGraphClient {
  /** `path` is relative to the API version, e.g. "act_123/campaigns". */
  get(path: string, params?: Record<string, string>): Promise<GraphResponse>;
  post(path: string, params: Record<string, string>): Promise<GraphResponse>;
  /** Follows a `paging.next` URL returned by a previous call. */
  getNext(url: string): Promise<GraphResponse>;
}

/**
 * A failed Graph API call. Carries only the error fields Meta returns — never the
 * request URL or parameters, which contain the access token.
 */
export class MetaApiError extends Error {
  constructor(
    message: string,
    readonly code: number | null,
    readonly subcode: number | null,
    readonly usageHeader: string | null = null,
  ) {
    super(message);
    this.name = "MetaApiError";
  }
}
