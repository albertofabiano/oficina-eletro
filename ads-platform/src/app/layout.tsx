import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "Tráfego Pago",
  description: "Gestão automatizada de anúncios",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    // Browser extensions (e.g. LanguageTool) add attributes to <html> before hydration.
    <html lang="pt-BR" suppressHydrationWarning>
      <body className="font-sans antialiased">{children}</body>
    </html>
  );
}
