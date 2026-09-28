// Minimal typings for the parts of the Meta Business SDK we use (the package ships none).
declare module "facebook-nodejs-business-sdk" {
  export class FacebookAdsApi {
    constructor(accessToken: string, locale?: string, crashLog?: boolean);
    static readonly VERSION: string;
    setShowHeader(flag: boolean): FacebookAdsApi;
    call(
      method: "GET" | "POST",
      path: string | string[],
      params?: Record<string, unknown>,
    ): Promise<Record<string, unknown> & { headers?: Record<string, string | undefined> }>;
  }
}
