# Plataforma de Tráfego Pago

## Contexto
Plataforma que automatiza a gestão de anúncios (Meta Ads e, depois, Google Ads).
Primeiro atende os negócios de serviço do dono; depois será vendida para
assistências técnicas clientes do FixaOS (fixaos.com.br), com integração via webhook.

## Stack
- Next.js (App Router) + TypeScript estrito
- Supabase (Postgres, Auth, Vault), região São Paulo
- Trigger.dev para tarefas agendadas
- Tailwind + shadcn/ui
- SDK oficial facebook-business (Node)
- Zod para validar toda entrada externa
- Vitest para testes

## Regras inegociáveis
- NUNCA alterar status ou orçamento de campanha sem aprovação humana registrada
  no banco. Toda ação de escrita nas APIs de anúncio passa por uma fila de aprovação.
- Existe modo DRY_RUN (variável de ambiente): quando ativo, nenhuma escrita é
  enviada às plataformas, apenas registrada.
- Tokens de acesso ficam criptografados (Supabase Vault). Nunca logar, nunca
  retornar ao frontend.
- Dinheiro sempre em centavos inteiros (integer), nunca float.
- Datas no fuso America/Sao_Paulo.
- Toda coleta recoleta os últimos 7 dias (atribuição muda).
- Respeitar rate limit da Meta (header x-business-use-case-usage): desacelerar acima de 75%.
- Plataformas de anúncio implementam uma interface comum AdPlatform, para
  facilitar adicionar Google Ads depois.

## Padrões
- Código e nomes em inglês; textos da interface em português.
- Migrations versionadas do Supabase para todo schema.
- Toda regra de otimização/alerta tem teste com dados fictícios.
- Antes de implementar algo grande, apresente o plano e aguarde aprovação.
- Commits pequenos e descritivos.

<!-- BEGIN:nextjs-agent-rules -->

# This is NOT the Next.js you know

This version has breaking changes — APIs, conventions, and file structure may all differ from your training data. Read the relevant guide in `node_modules/next/dist/docs/` (resolved from this file's directory; in monorepos the `next` package may not be visible from the repo root) before writing any code. Heed deprecation notices.

This block is written and re-added by `next dev` — verify at `node_modules/next/dist/server/lib/generate-agent-files.js`. Removing it from a diff only re-creates the uncommitted change; committing it with your work keeps the tree clean.

<!-- END:nextjs-agent-rules -->
