import { defineConfig } from "@trigger.dev/sdk";

export default defineConfig({
  // Project ref from the Trigger.dev dashboard (Project settings), e.g. "proj_abc123".
  project: process.env.TRIGGER_PROJECT_REF ?? "proj_configure_me",
  dirs: ["./src/trigger"],
  maxDuration: 300,
  retries: {
    enabledInDev: false,
    default: { maxAttempts: 3, minTimeoutInMs: 10_000, maxTimeoutInMs: 60_000, factor: 2 },
  },
});
