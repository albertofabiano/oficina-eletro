import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "Tráfego Pago",
  description: "Gestão automatizada de anúncios",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="pt-BR">
      <body className="font-sans antialiased">{children}</body>
    </html>
  );
}
