import { FakeAdPlatform } from "./fake-platform";
import { createSdkGraphClient } from "./meta/sdk-client";
import { MetaAdsPlatform } from "./meta/meta-platform";
import type { AdPlatform } from "./platform";
import type { PlatformId } from "./types";

export interface PlatformAccount {
  /** Internal ad account id, used to look up the access token. */
  id: string;
  platform: PlatformId;
}

/** Reads an ad account's access token from Supabase Vault (server only). */
export type TokenLoader = (adAccountId: string) => Promise<string>;

/** Resolves the implementation for an ad account, with its credentials. */
export async function getAdPlatform(account: PlatformAccount, loadToken: TokenLoader): Promise<AdPlatform> {
  switch (account.platform) {
    case "fake":
      return new FakeAdPlatform();
    case "meta":
      return new MetaAdsPlatform(createSdkGraphClient(await loadToken(account.id)));
  }
}
