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

## Coleta de dados

- As plataformas implementam `AdPlatform` (`src/lib/ads/platform.ts`):
  - `MetaAdsPlatform` (`src/lib/ads/meta/`) usa o SDK oficial da Meta para ler campanhas
    e métricas diárias (investimento, impressões, cliques e leads) e para as escritas
    aprovadas. Cada chamada respeita o header `x-business-use-case-usage` (desacelera
    acima de 75% e espera o bloqueio informado pela Meta).
  - `FakeAdPlatform` simula campanhas para a conta de demonstração.
- A conta da Meta é conectada em **/configuracoes** com o ID da conta e um token de
  usuário do sistema (`ads_read` + `ads_management`). O token é validado na Meta (a conta
  precisa estar em BRL e no fuso America/Sao_Paulo) e guardado no **Supabase Vault** pela
  função `connect_ad_account`; só o service role consegue lê-lo
  (`get_ad_account_token`). Erros do SDK são refeitos sem a URL da requisição, para o
  token nunca aparecer em logs.
- O último erro de coleta de cada conta fica em `ad_accounts.last_sync_error` e aparece
  em Configurações.
- A coleta (`src/lib/sync/`) importa 60 dias na primeira vez e depois recoleta sempre
  os últimos 7 dias, com upsert por campanha e dia (sem duplicar).
- Roda todo dia às 06:00 (São Paulo) pelo Trigger.dev (`src/trigger/`) e também pelo
  botão **Sincronizar agora** do painel.
- Precisa de `SUPABASE_SECRET_KEY` no servidor (nunca com prefixo `NEXT_PUBLIC_`).

Publicar a tarefa agendada (as variáveis `NEXT_PUBLIC_SUPABASE_URL`, `SUPABASE_SECRET_KEY`
e `DRY_RUN` precisam estar cadastradas no ambiente Production do Trigger.dev):

```bash
npx trigger.dev@4 login
npx trigger.dev@4 deploy
```

## Sugestões e aprovações

- Depois de cada coleta, as regras (`src/lib/optimization/rules.ts`) analisam os últimos
  7 dias completos e colocam **sugestões** na fila (`action_requests`, status `pending`):
  - pausar campanha ativa que gastou R$ 50+ sem nenhum lead;
  - reduzir 20% o orçamento quando o custo por lead passa de 1,5x a média da conta;
  - aumentar 20% o orçamento quando o custo por lead é ≤ 0,6x a média, com 5+ leads e
    quase todo o orçamento em uso.
- Não repete sugestões pendentes nem as rejeitadas nos últimos 7 dias.
- Em **/aprovacoes** o usuário aprova ou rejeita; a decisão é gravada pela função
  `decide_action_request` (quem e quando) e auditada.
- O executor (`src/lib/queue/execute.ts`) é o único código que chama as escritas da
  plataforma, só para pedidos `approved`. Com `DRY_RUN=true` apenas registra a execução
  como simulação.

## Estado atual

- **Login** (`/login`) com e-mail e senha pelo Supabase Auth. Toda página exige login;
  no primeiro acesso o usuário cria sua empresa (`/onboarding`).
- **Campanhas** (`/campanhas`): lista com filtro por status e período; cada campanha
  tem uma página de detalhe com indicadores, gráfico, tabela dia a dia e o histórico
  de sugestões e decisões.
- **Painel** (`/dashboard`): indicadores com comparação ao período anterior,
  gráfico diário de investimento x leads, alertas e tabela de campanhas.
  Período por `?periodo=7|14|30`, terminando ontem (fuso America/Sao_Paulo).
- O painel lê do banco (`SupabaseDashboardDataSource`). Sem contas conectadas, leva para
  Configurações ou oferece uma conta de demonstração com campanhas simuladas.
- **Configurações** (`/configuracoes`): conectar, reconectar (trocar token) e desconectar
  contas da Meta; remover a conta de demonstração.
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
