import { defineConfig } from "@trigger.dev/sdk";

export default defineConfig({
  // Project ref from the Trigger.dev dashboard (not a secret).
  project: process.env.TRIGGER_PROJECT_REF ?? "proj_yslcuwpqmfulbevkzcws",
  // Node 21 (the "node" default) is being retired; pin the current LTS.
  runtime: "node-22",
  dirs: ["./src/trigger"],
  maxDuration: 300,
  retries: {
    enabledInDev: false,
    default: { maxAttempts: 3, minTimeoutInMs: 10_000, maxTimeoutInMs: 60_000, factor: 2 },
  },
});
