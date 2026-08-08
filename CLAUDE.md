# FixaOS — notas de contexto

## Stack e comandos
PHP MVC próprio (sem framework), sem Composer e sem Node/npm — não há
`composer.json` nem `package.json` no projeto.

- **Rodar local**: `php -S localhost:8000 -t public` (o front controller é
  `public/index.php`; autoload é um `spl_autoload_register` manual mapeando
  `App\Foo\Bar` → `app/Foo/Bar.php`, sem PSR-4 via Composer).
- **Lint**: `php -l arquivo.php` por arquivo (não existe um comando "lint tudo"
  configurado). É o único guard-rail automatizado do projeto — rode sempre
  antes de commitar.
- **Build**: não existe. CSS/JS são arquivos estáticos servidos direto de
  `public/css/` e `public/js/`; Bootstrap 5 e Bootstrap Icons vêm de CDN
  (`cdn.jsdelivr.net`), não são empacotados localmente.
- **Testes**: não existe suíte automatizada (sem PHPUnit, sem pasta `tests/`).
  Toda verificação é manual — `php -l` garante só que o arquivo parseia, não
  que o comportamento está certo.
- **Banco**: migrations em `database/migrations/*.sql`, aplicadas manualmente
  via `mysql -u fixaos -p fixaos < arquivo.sql` (ver "Padrão de deploy"
  abaixo — nunca automático).

## Convenções de nomenclatura
- **Controllers**: `app/Controllers/NomeController.php`, PascalCase +
  sufixo `Controller` (`OrdemServicoController`, `AgendaController`).
- **Models**: `app/Models/Nome.php`, PascalCase singular
  (`OrdemServico`, `Cliente`, `Marketplace`), estendem `App\Core\Model`.
  **Nem todo domínio tem Model** — alguns controllers (ex.: `AgendaController`)
  falam direto com `DB::pdo()` via SQL cru, sem passar por uma classe Model.
  Isso é inconsistência real do projeto, não um padrão a seguir em código novo.
- **Views**: `app/Views/{dominio}/{acao}.php`, minúsculo/snake_case
  (`os/show.php`, `agenda/index.php`, `crm/pipeline.php`). Renderizadas via
  `$this->view('dominio.acao', $dados, $layout='main')`, que inclui
  `app/Views/layouts/{layout}.php` por fora do conteúdo.
- **Rotas**: `routes/web.php`, um único arquivo, path em português
  (`/os`, `/agenda`, `/crm`), REST-ish (`GET/POST/DELETE` + `{id}` nos
  handlers), sempre com `['AuthMiddleware']` pra área logada.
- **Helpers globais**: funções soltas em `app/Helpers/functions.php`
  (~630 linhas), snake_case, carregadas globalmente por
  `public/index.php` (`url()`, `money()`, `e()`, `date_br()`, `csrf_field()`,
  `csrf_verify()`, `moeda_float()`, `badge_status_os()`, `pagination()` etc.).
  Não existe namespace pra elas — são globais mesmo.
- **CSS**: variáveis de tema em `public/css/tokens.css` (`--surface-*`,
  `--text-*`, `--accent`, `--border` etc., com bloco `[data-theme="dark"]`
  separado). `public/css/app.css` é legado, carregado *antes* de tokens.css,
  cheio de `!important` com valores antigos — qualquer CSS novo em área que
  o app.css também mexe precisa checar conflito de especificidade ali.

## Componentes e helpers compartilhados
- **Layout principal**: `app/Views/layouts/main.php` — shell do app logado
  (topbar, sidebar, Mentor IA flutuante, notificações). Outros layouts:
  `landing.php` (site público), `master.php` (painel admin/master),
  `auth.php` (login/cadastro), `print*.php` (um por tipo de documento
  impresso — não compartilham um layout comum entre si).
- **Partials reutilizáveis**: convenção `_nome.php` (prefixo underscore),
  incluídos via `include __DIR__ . '/_nome.php'`. Só existem dois hoje:
  `app/Views/layouts/_botao_wa_pdf.php` (botão "Enviar por WhatsApp" usado
  em todas as telas de impressão de OS) e
  `app/Views/marketplace/partials/_publico_body.php`.
- **JS compartilhado**: `public/js/` tem só utilitários pontuais
  (`masks.js`, `theme.js`, `offline-cache.js`, `marketplace-ajax.js`) — não
  há um framework de componentes JS; cada view escreve seu próprio
  `<script>` inline, geralmente uma IIFE por seção da página.

## Domínio (resumo)
- **Empresa**: tenant do sistema (`empresas`); praticamente toda tabela tem
  `empresa_id` e todo Controller filtra por `$this->empresaId()`.
- **Usuário**: pessoa que loga (`usuarios`), pertence a uma empresa, pode ser
  técnico responsável por uma OS ou evento de agenda (`usuario_id` /
  `tecnico_id` conforme a tabela).
- **Cliente**: dono do equipamento (`clientes`), tem CPF/CNPJ, telefone,
  WhatsApp — é o alvo de mensagens automáticas (comprovantes, recados,
  link de acompanhamento).
- **Ordem de Serviço (OS)** (`ordens_servico`): o núcleo do sistema — um
  reparo, do orçamento ao fechamento. Tem status configurável por empresa
  (`os_status`, com `tipo` fixo: aberta/em_andamento/aguardando/concluida/
  entregue/cancelada — o `tipo` é que dirige toda a lógica de negócio,
  como cobrança e garantia; `codigo`/`nome` são cosméticos e editáveis).
  Serviços e peças da OS ficam em `os_servicos`/`os_pecas`; laudo técnico é
  HTML rich-text direto na coluna `laudo_tecnico`; fechamento sem cobrança
  (ex.: "Sem Conserto", "Recusado") é sinalizado por `fechada_sem_receita`,
  que persiste mesmo depois do status virar "Fechado".
- **Evento de agenda** (`agenda`): ver mapeamento completo da tela abaixo —
  é a entidade menos madura do domínio: pode opcionalmente linkar
  `cliente_id`/`os_id`/`usuario_id`, mas hoje nenhuma tela realmente popula
  esses vínculos.

## Mapeamento: tela de Agenda (investigação — nenhum código alterado)

**Arquivos:**
- Controller: `app/Controllers/AgendaController.php` — métodos `index()`
  (calendário mensal + lista), `salvar()` (cria OU edita, ramifica por
  `evento_id` vir preenchido ou não), `atualizar($id)` (edição por rota
  própria — **não tem chamador em lugar nenhum**, é código morto),
  `excluir($id)`, `eventos()` (retorna JSON no formato do FullCalendar
  via `/api/agenda` — **também sem chamador na view**, código morto).
- View única: `app/Views/agenda/index.php` — renderiza tudo: barra de
  navegação de mês (topo), a grade do calendário mensal, a lista "Eventos
  de {mês}" e o modal de novo/editar evento, tudo no mesmo arquivo (sem
  partials). Não há barra de topo própria da Agenda — é a topbar genérica
  do `layouts/main.php`.
- Rotas (`routes/web.php`): `GET /agenda`, `POST /agenda` (salvar),
  `POST /agenda/{id}` (atualizar — morta), `DELETE /agenda/{id}` (excluir),
  `GET /api/agenda` (eventos JSON — morta).

**Modelo de dados** — tabela `agenda` (`database/migrations/001_schema.sql`,
sem Model class, controller usa `DB::pdo()` direto):
`id, empresa_id, titulo, descricao, tipo ENUM('visita','coleta','entrega',
'reuniao','ligacao','os','outro') DEFAULT 'outro', cliente_id, os_id,
usuario_id, data_inicio DATETIME NOT NULL, data_fim, dia_todo, cor
VARCHAR(7), status ENUM('agendado','confirmado','cancelado','concluido')
DEFAULT 'agendado', lembrete_minutos, link_externo, criado_em`.
FKs: `cliente_id → clientes`, `os_id → ordens_servico`, `usuario_id →
usuarios` (todas `ON DELETE SET NULL`) — a estrutura permite ligar o evento
a Cliente/OS/Técnico, mas **o modal de criar/editar evento não tem campo
nenhum pra isso** (só título, tipo, responsável, início/fim, cor,
descrição) — todo evento criado pela UI fica com `cliente_id`/`os_id` nulos,
mesmo a query de listagem já fazendo `LEFT JOIN` com `clientes` esperando
mostrar o nome. `tipo` "Outro" e `status` "Agendado" nos prints são os
`DEFAULT` do enum — nada na aplicação muda o `status` depois da criação.

**Stack da tela**: sem biblioteca de calendário (nada de FullCalendar/
react-big-calendar) — a grade mensal é construída à mão em PHP (`mktime()` +
`date()` num loop `while`, montando `<div>`s via `echo`/concatenação de
string dentro da própria view). Sem lib de datas em JS. UI é Bootstrap 5
puro (modal, tabela, badges); o "drag-and-drop" e a edição usam só um
`<script>` inline no fim da view (função `editarEvento()` que lê um
`data-*`/JSON do botão e preenche o modal).

**5 pontos de dívida técnica pra um redesign:**
1. **Vínculo Cliente/OS/Técnico existe no banco mas não na UI** — o maior
   gap: pra qualquer redesign fazer sentido (ex.: mostrar eventos na tela
   da OS, ou a agenda puxar OS automaticamente), precisa primeiro adicionar
   os campos de busca/seleção de cliente e OS no modal.
2. **Código morto**: `AgendaController::atualizar()` e `::eventos()` (rota
   `/api/agenda`) não são chamados por nada — confundem quem for entender
   o fluxo achando que há uma edição "oficial" via rota própria ou uma API
   JSON já pronta pra plugar um calendário JS.
3. **`status` é decorativo** — sempre `'agendado'` na prática, porque não
   existe nenhuma ação de UI que mude pra confirmado/cancelado/concluído.
   Um redesign que mantenha esse badge precisa ou implementar as transições
   ou remover o campo da tela pra não mentir pro usuário.
4. **Grade mensal hand-rolled, sem testes** — toda a lógica de dia-da-semana/
   virada de mês/preenchimento de células vazias está em `echo` de HTML
   misturado com cálculo de data na view, sem nenhum teste cobrindo casos de
   borda (mês com 28/29/30/31 dias, ano bissexto, primeiro dia do mês caindo
   em cada dia da semana). Reescrever isso à mão de novo repete o risco;
   vale considerar uma lib JS (FullCalendar) alimentada pelo endpoint
   `/api/agenda` que já existe (só precisa ligar ele em vez de descartar).
5. **Sem Model, sem teste automatizado nenhum no projeto** — qualquer
   redesign da Agenda (ou de qualquer tela) não tem rede de segurança
   automatizada; a única verificação possível hoje é `php -l` (sintaxe) +
   teste manual no navegador. Vale pelo menos introduzir um `Agenda` Model
   (padronizando com `OrdemServico`/`Cliente`) antes de crescer a lógica.

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
