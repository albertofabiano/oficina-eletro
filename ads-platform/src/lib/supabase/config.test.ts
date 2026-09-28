import { describe, expect, it } from "vitest";
import { supabaseConfig } from "./config";

const key = "x".repeat(40);

describe("supabaseConfig", () => {
  it("accepts the anon key", () => {
    expect(supabaseConfig({ NEXT_PUBLIC_SUPABASE_URL: "https://abc.supabase.co", NEXT_PUBLIC_SUPABASE_ANON_KEY: key }))
      .toEqual({ url: "https://abc.supabase.co", key });
  });

  it("prefers the publishable key", () => {
    const config = supabaseConfig({
      NEXT_PUBLIC_SUPABASE_URL: "https://abc.supabase.co",
      NEXT_PUBLIC_SUPABASE_ANON_KEY: key,
      NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY: "y".repeat(40),
    });
    expect(config.key).toBe("y".repeat(40));
  });

  it("fails clearly when missing", () => {
    expect(() => supabaseConfig({})).toThrow();
  });
});
