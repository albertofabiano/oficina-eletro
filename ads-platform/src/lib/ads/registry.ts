import { FakeAdPlatform } from "./fake-platform";
import type { AdPlatform } from "./platform";
import type { PlatformId } from "./types";

/** Resolves the implementation for an ad account's platform. */
export function getAdPlatform(platform: PlatformId): AdPlatform {
  switch (platform) {
    case "fake":
      return new FakeAdPlatform();
    case "meta":
      throw new Error("Integração com a Meta ainda não configurada.");
  }
}
