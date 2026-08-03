# FixaOS — notas de contexto

## Pendências

### Redesign da sidebar (trilha de ícones expansível)
Spec completa já foi discutida e aprovada em detalhe (2026-08-03): converter a
sidebar atual (10 gradientes por item, ~246px fixos, Clientes/Financeiro
duplicados) numa trilha de ícones expansível:
- Recolhida 58px (só ícones) / expandida 212px (ícones + rótulos)
- Expande por hover com atraso de 300ms, ou fica travada aberta via alfinete
  (preferência persistida no perfil do usuário, mesmo padrão da preferência de
  tema — `usuarios.tema` / `POST /preferencias/tema`)
- Hover expandido sobrepõe o conteúdo (position:fixed já resolve isso);
  fixada desloca o `#main`
- 3 grupos com divisores (sem rótulos de texto): operação diária / cadastros e
  gestão / canais e sistema
- Badges: ponto quando recolhida, número quando expandida — OS atrasadas em
  --danger, Atendimento pendente em neutro (não é a mesma urgência)
- Tooltip CSS puro (500ms) quando recolhida; foco por teclado expande na hora
- Mobile (<900px): barra inferior fixa (Início/OS/+Nova OS/Caixa/Menu) em vez
  da trilha — ícone sem rótulo em tela pequena não funciona

Foi **implementada por completo** (commit `1b3ad48` no branch
`claude/fixaos-dev-setup-9npe8x`) e depois **revertida a pedido do usuário**
(commit `56dbaf2`, já deployado no VPS) — não porque estivesse quebrada, mas
porque o usuário quis retomar/testar com calma "à noite". Antes de reimplementar:

- `git show 1b3ad48` no branch tem a implementação completa de referência
  (main.php CSS+HTML+JS, migration `021_sidebar_fixada.sql`, rota
  `/preferencias/sidebar`, `DashboardController::salvarSidebarFixada()`).
- Achei e corrigi ali 3 conflitos reais de especificidade com
  `public/css/app.css` (legado, ainda carregado antes de tokens.css) que usa
  `!important` em `#sidebar`, `#main` e `.sb-group-btn` com valores antigos —
  qualquer nova tentativa de redesenho da sidebar vai esbarrar nisso de novo.
- Ao reimplementar, considerar se vale a pena excluir esse trecho de app.css
  em vez de só sobrepor com !important (mais limpo a longo prazo).

## Padrão de deploy deste projeto
Sem CI/CD automático — todo commit em `claude/fixaos-dev-setup-9npe8x` precisa
ser puxado manualmente no VPS pelo usuário:
```bash
cd /var/www/fixaos
git fetch github <branch>
git checkout github/<branch> -- <arquivos>
php -l <arquivos .php>
```
(o remote no VPS se chama `github`, não `origin`). Migrations em
`database/migrations/*.sql` são rodadas manualmente via
`mysql -u fixaos -p fixaos < arquivo.sql`, nunca automaticamente.
