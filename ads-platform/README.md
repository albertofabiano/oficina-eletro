# Tráfego Pago

Plataforma de gestão automatizada de anúncios (Meta Ads; Google Ads depois).

## Rodando

```bash
npm install
cp .env.example .env.local
npm run dev        # http://localhost:3000/dashboard
npm test           # Vitest
npm run typecheck
```

## Banco de dados (Supabase)

As migrations ficam em `supabase/migrations/` e são aplicadas com a CLI do Supabase:

```bash
npx supabase login
npx supabase link --project-ref SEU_PROJECT_REF
npx supabase db push
```

Desenvolvimento local (requer Docker): `npx supabase start` sobe banco, autenticação e
API; os dados de acesso aparecem no terminal para colocar no `.env.local`.

Testes do banco (isolamento entre empresas e fila de aprovação), com um Postgres local:

```bash
./scripts/test-db.sh
```

## Estado atual

- **Login** (`/login`) com e-mail e senha pelo Supabase Auth. Toda página exige login;
  no primeiro acesso o usuário cria sua empresa (`/onboarding`).
- **Painel** (`/dashboard`): indicadores com comparação ao período anterior,
  gráfico diário de investimento x leads, alertas e tabela de campanhas.
  Período por `?periodo=7|14|30`, terminando ontem (fuso America/Sao_Paulo).
- Os dados vêm de `MockDashboardDataSource` (fictícios). A integração com
  Supabase/Meta substitui essa classe implementando `DashboardDataSource`.
- Alertas são somente leitura: nenhuma ação é executada nas campanhas.

## Estrutura

```
src/
├── app/login/            # Login e cadastro
├── app/onboarding/       # Criação da empresa no primeiro acesso
├── app/auth/confirm/     # Retorno dos links de e-mail
├── app/dashboard/        # Página do painel
├── proxy.ts              # Exige login e renova a sessão
├── components/ui/        # Componentes base (estilo shadcn/ui)
├── components/dashboard/ # Componentes do painel
└── lib/
    ├── ads/              # Tipos comuns às plataformas
    ├── auth/             # Sessão, validação e rotas públicas
    ├── supabase/         # Clientes Supabase (servidor e proxy)
    ├── dashboard/        # Métricas, alertas, períodos e fontes de dados
    ├── dates.ts          # Datas ISO no fuso de São Paulo
    ├── money.ts          # Dinheiro em centavos inteiros
    └── env.ts            # Variáveis de ambiente (DRY_RUN)
supabase/migrations/      # Schema versionado (tabelas, RLS, fila de aprovação)
tests/db/                 # Testes SQL do banco
```
