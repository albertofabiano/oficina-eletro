import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "Tráfego Pago",
  description: "Gestão automatizada de anúncios",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    // Browser extensions (e.g. LanguageTool, ColorZilla) add attributes to <html> and <body> before hydration.
    <html lang="pt-BR" suppressHydrationWarning>
      <body className="font-sans antialiased" suppressHydrationWarning>{children}</body>
    </html>
  );
}
