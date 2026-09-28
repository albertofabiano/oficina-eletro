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

## Estado atual

- **Painel** (`/dashboard`): indicadores com comparação ao período anterior,
  gráfico diário de investimento x leads, alertas e tabela de campanhas.
  Período por `?periodo=7|14|30`, terminando ontem (fuso America/Sao_Paulo).
- Os dados vêm de `MockDashboardDataSource` (fictícios). A integração com
  Supabase/Meta substitui essa classe implementando `DashboardDataSource`.
- Alertas são somente leitura: nenhuma ação é executada nas campanhas.

## Estrutura

```
src/
├── app/dashboard/        # Página do painel
├── components/ui/        # Componentes base (estilo shadcn/ui)
├── components/dashboard/ # Componentes do painel
└── lib/
    ├── ads/              # Tipos comuns às plataformas
    ├── dashboard/        # Métricas, alertas, períodos e fontes de dados
    ├── dates.ts          # Datas ISO no fuso de São Paulo
    ├── money.ts          # Dinheiro em centavos inteiros
    └── env.ts            # Variáveis de ambiente (DRY_RUN)
```
