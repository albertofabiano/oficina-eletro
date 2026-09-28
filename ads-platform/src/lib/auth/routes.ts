/** Paths reachable without a session. Everything else requires login. */
const PUBLIC_PATHS = ["/login", "/auth/"];

export function isPublicPath(pathname: string): boolean {
  return PUBLIC_PATHS.some((path) => (path.endsWith("/") ? pathname.startsWith(path) : pathname === path));
}

/**
 * Where to send the user after login. Only same-site relative paths are
 * accepted so the redirect cannot be abused to leave the app.
 */
export function safeNextPath(raw: unknown, fallback = "/dashboard"): string {
  if (typeof raw !== "string" || !raw.startsWith("/") || raw.startsWith("//") || raw.includes("\\")) {
    return fallback;
  }
  return raw;
}
