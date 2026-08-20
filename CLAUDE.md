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
- **Testes**: não tem PHPUnit nem Composer, mas existe `tests/` — scripts PHP
  simples, sem framework, cada um com seu próprio runner de asserções
  (`assert_igual()`/`assert_verdadeiro()`, exit 1 se algo falhar) e rodados
  direto com `php tests/arquivo_test.php`. Hoje só cobre a matemática pura de
  recorrência (`tests/rrule_test.php`, ver `app/Helpers/rrule.php`) — lógica
  que mexe em banco (ex.: exceções de série) segue só verificação manual, já
  que não há fixture/banco de teste no projeto. Fora isso, `php -l` continua
  sendo o único guard-rail automatizado.
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

> **Desatualizado**: este mapeamento é de antes de uma sequência de reescritas grandes da
> Agenda (grade semântica, filtros, visões Semana/Dia/Técnicos, recorrência via RRULE,
> vínculo Cliente/OS, drag-and-drop, indicadores/Próximos 7 dias, acabamento
> mobile/acessibilidade/atalhos, e lembretes — ver "Lembretes de agenda" abaixo). Vários dos
> gaps listados aqui (código morto, sem vínculo Cliente/OS, status decorativo, grade sem
> teste) já foram endereçados. Mantido por valor histórico; não confie nele sem checar o
> código atual.

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

## Lembretes de agenda

Lembrete interno pro técnico (in-app: na hora, 15 min, 1 h ou 1 dia antes — pode marcar mais
de um) e um lembrete opcional pro cliente (WhatsApp ou e-mail, mensagem editável) por evento
de agenda. Peças:

- **Config por evento**: colunas em `agenda` (migration `030_agenda_lembretes.sql`) —
  `lembrete_tecnico_offsets` (CSV de minutos antes, ex. `"0,15,60,1440"`) e
  `lembrete_cliente_ativo`/`_canal`/`_offset`/`_mensagem`. Vivem na própria linha, igual
  `cliente_id`/`os_id` — inclusive em exceções de série materializadas (editar "somente este
  evento" pode ter lembretes diferentes do resto da série).
- **Fila + log, mesma tabela**: `agenda_lembretes_fila` — cada linha é um disparo agendado
  (`status='pendente'`) que vira o próprio registro do envio quando processada
  (`enviado`/`falha`/`cancelado`, com `enviado_em`/`destino`/`mensagem`/`ultimo_erro`).
- **`App\Services\Lembretes\AgendaLembreteService`** — `reagendar($agendaId, $eid)` (chamado
  em todo ponto de `AgendaController` que grava `agenda`: criar, editar — inclusive os 3
  escopos de série —, e nas ações rápidas de status), `cancelarPendentes(...)` (exclusão/
  cancelamento) e `processarFila()` (o "worker": envia o que já venceu). Séries recorrentes
  não têm disparo materializado pra sempre — só uma janela de 35 dias à frente
  (`JANELA_SERIE_DIAS`), re-topada a cada `reagendar()` da série — mesmo princípio de não
  materializar ocorrências do `rrule.php`. Fuso: `date()`/`strtotime()` já operam em
  `America/Sao_Paulo` (`date_default_timezone_set` em `public/index.php` e no script de
  cron), então a aritmética de horário do serviço não precisa de `DateTimeZone` explícito.
- **Cancelamento é respeitado de verdade**: nunca dispara pra evento com
  `status='cancelado'` OU `recorrencia_excluida=1` — checado tanto ao agendar quanto de novo
  no momento do envio (`processarFila()` re-resolve o evento efetivo), então cancelar
  DEPOIS de já ter enfileirado ainda impede o disparo.
- **Provedor plugável** (`app/Services/Lembretes/`): `NotificacaoProviderInterface` é o
  contrato pro envio ao CLIENTE (WhatsApp/e-mail — o lembrete do técnico nunca passa por
  aqui, é sempre `NotificacaoService::criar()`, in-app). Duas implementações já prontas:
  - `FakeNotificacaoProvider` (**padrão**) — não envia nada de verdade, só grava em
    `storage/logs/lembretes_fake.log`. Seguro pra dev/homologação não mandar mensagem real.
  - `AppNotificacaoProvider` — reaproveita o que a empresa já pode ter configurado
    (`WhatsAppService`, Evolution API por empresa; `EmailService`, SMTP).

  **Pra ligar o envio de verdade**: troque `'provider' => 'fake'` por `'provider' => 'app'`
  em `config/lembretes.php`. Pra um fornecedor DIFERENTE desses dois (Twilio, um SMTP
  dedicado só pra lembretes etc.), implemente `NotificacaoProviderInterface` numa classe
  nova em `app/Services/Lembretes/` e adicione o `case` correspondente em
  `NotificacaoProviderFactory::criar()` — não precisa mexer em mais nada (nem no
  `AgendaLembreteService`, nem no controller, nem na view).
- **Processamento sem cron real**: a fila roda via poller throttled (1x/min, arquivo
  marcador em `/tmp`, global — não é por empresa) plugado em
  `NotificacaoController::index()`/`api()`, mesmo padrão de `gerarThrottled()` já usado pras
  notificações in-app. Com cron real (recomendado em produção — não depende de alguém com o
  FixaOS aberto no navegador), usar `scripts/processar_lembretes_agenda.php`:
  ```
  * * * * * php /var/www/fixaos/scripts/processar_lembretes_agenda.php >> /var/www/fixaos/storage/logs/lembretes_cron.log 2>&1
  ```
- **Mensagem do cliente**: template com variáveis `{{cliente}}`/`{{data}}`/`{{hora}}`/
  `{{os}}`/`{{endereco}}`, resolvidas no momento do AGENDAMENTO (não do envio — a mensagem
  fica congelada na fila desde então, é o que fica registrado no log de qual foi mandado).
- **Alerta sonoro no vencimento** (`agenda.alerta_sonoro`, migration `032_agenda_alerta_sonoro.sql`,
  toggle "🔊 Alerta sonoro no vencimento" no modal de evento): gatilho independente dos
  checkboxes de "Lembrete interno" — sempre dispara no instante 0 (vencimento), mesmo que
  "Na hora" não esteja marcado. `AgendaLembreteService::agendarOcorrencia()` inclui o offset 0
  na fila sempre que `alerta_sonoro=1` e marca a linha (`agenda_lembretes_fila.som=1`); ao
  processar, `NotificacaoService::criar()` recebe `tipo='lembrete_agenda_som'` em vez de
  `'lembrete_agenda'` — é só esse `tipo` que diferencia "toca som" de um lembrete normal, não
  há coluna de som em `notificacoes`. Quem decide TOCAR é o front-end: `verificarAlertasSonoros()`
  em `layouts/main.php`, dentro do polling de notificações que já roda em toda página logada
  (`carregarNotifs()`, 2 em 2 min) — não é exclusivo da tela de Agenda. Beep sintetizado via Web
  Audio API (`tocarBeepAlerta()`), sem arquivo de áudio; browsers com política de autoplay
  podem exigir uma interação prévia na página pra permitir som. Só beepa notificações NOVAS
  desde que a aba foi aberta (não beepa histórico não lido ao carregar a página) — dedup por
  `Set` de ids em memória, não sobrevive a F5.

## Painel "Hoje" (Agenda)

Primeira coisa visível ao abrir a Agenda, acima da toolbar (`_painel_hoje.php`, dentro de
`#agendaPainelHoje`) — resumo operacional do dia, fase 1 da visão de "painel de comando da
assistência" discutida com o usuário 2026-08-09 (fase 2, distribuição automática de serviços
por técnico via IA, ainda não tem groundwork: faltam campos de domínio como especialidade do
técnico e tempo estimado por tipo de serviço). `AgendaController::painelHoje()` monta:

- **3 contagens de OS por status** (`os_status.tipo`, não `agenda.status`): aguardando
  atendimento (`tipo='aberta'`), orçamentos aguardando aprovação (`tipo='aguardando'`),
  atrasados — mesma definição de `NotificacaoService::verificarOsAtrasadas()`
  (`data_previsao < CURDATE()` e `tipo NOT IN ('entregue','cancelada','concluida')`), de
  propósito, pra não inventar uma segunda noção de "atrasada" divergente da que já vira
  notificação. Os cartões desses 3 não filtram a lista de OS ao clicar (só levam pra `/os`) —
  a tela de OS filtra por `status_id` específico, não por `tipo` (uma empresa pode ter vários
  status com o mesmo tipo), então um link "preciso" exigiria mudar `OrdemServicoController`
  também; fora de escopo por ora.
- **Entregas previstas hoje**: conta eventos de agenda `tipo='entrega'` com `data_inicio` hoje,
  reaproveitando `AgendaController::carregarEventosDaJanela()` (mesma fonte de dados das 4
  visões — já expande recorrência). Link vai pra visão Dia de hoje filtrada por esse tipo.
- **Horas livres hoje / ocupação por técnico**: reaproveita a "jornada" configurada em
  `config/eventos_agenda.php` (mesma referência da barra de ocupação da visão Técnicos,
  `_grade_tecnicos.php`) e a duração de cada evento via `agenda_evento_duracao_horas()`
  (`app/Helpers/functions.php` — extraída pra função global justamente pra painel Hoje e
  `_grade_tecnicos.php` não divergirem no cálculo). "Livres hoje" soma, por técnico ativo,
  `max(0, jornada - horas agendadas)` — um técnico estourado (>100%) contribui 0, não negativo.
- Fica dentro de `#agendaPainelHoje`, que `agendaAtualizarGrade()` (JS) reconcilia junto com a
  grade/indicadores/Próximos 7 dias depois de qualquer ação (arrastar, ação rápida de status)
  — sem isso, um arraste que muda a ocupação de hoje ficaria com o número velho até um F5.
- **Nota**: `agenda.status='atrasado'` (usado pelo card "Em atraso" mensal em `_indicadores.php`,
  diferente deste painel) nunca é escrito por nenhum código atual — é um valor morto, sempre
  0. Não fazia parte deste trabalho consertar (métrica diferente, no card antigo), só ficou
  claro ao investigar a definição de "atrasado" certa pra usar aqui.

## Financeiro: filtro de data e aviso de duplicata

Incidente real (2026-08-10, empresa "Clínica dos Eletros" via WhatsApp): cliente lançou uma
despesa (ENERGISA, vencimento 22/07), a tela de Fluxo de Caixa não mostrou (filtro padrão é só
o MÊS ATUAL — `FinanceiroController::index()`, `$inicio = date('Y-m-01')`), ela achou que não
tinha salvo e lançou de novo — gerou duas linhas idênticas em `fin_lancamentos` (confirmado via
SQL direto: mesma descrição/valor/vencimento, `criado_em` 7 minutos de diferença). Não foi
instabilidade nem bug de gravação, foi o filtro de data escondendo um lançamento retroativo.
Duas melhorias em resposta:

- **Estado vazio da tabela nomeia o período** ("Nenhum lançamento entre X e Y", em vez de
  "Nenhum lançamento no período" genérico) + link "veja o último ano inteiro" (preserva
  tipo/status/categoria já filtrados, só troca o intervalo de data) — pra quem lançou algo fora
  do mês atual perceber isso ANTES de lançar de novo.
- **`FinanceiroController::verificarDuplicata()`** (`GET /api/financeiro/duplicata`, rota
  autenticada normal, atrás de `AuthMiddleware` como o resto de `/financeiro/*`): checa mesma
  tipo+descrição+valor+data_vencimento nas últimas 6h. O JS do `#formLancamento`
  (`fluxo_caixa.php`) intercepta o submit, consulta esse endpoint, e se achar `duplicado=true`
  mostra um `confirm()` com o horário do lançamento anterior antes de deixar salvar — não
  bloqueia (o usuário pode confirmar mesmo assim, é só um aviso), e falha de rede não trava o
  salvamento (`.catch()` deixa passar). `ignorar_id` no request evita falso positivo ao editar
  um lançamento existente contra ele mesmo.

## Financeiro: taxa de cartão faltando no atalho "Receber OS"

Segundo incidente real com a "Clínica dos Eletros" no mesmo dia (2026-08-10): OS 000252 paga no
cartão de débito não gerou a despesa "Taxa cartão" — diferente de outras OS pagas em débito no
mesmo período, que geraram normalmente. Não foi instabilidade nem erro fatal (checado o log do
PHP-FPM no horário exato, nada lá) — era um caminho de código genuinamente incompleto.

**Causa**: existem DOIS lugares que registram pagamento de OS, e só um tinha a lógica de taxa:
- `OrdemServicoController::fechar()` (fechar a OS pela tela dela) — sempre teve: calcula a taxa
  via `taxa_cartao_configurada()`, grava uma linha em `os_pagamentos` e, se a taxa for > 0, uma
  despesa "Taxa cartão" categorizada.
- `FinanceiroController::pagarOs()` (botão "Receber OS" no quadro "OS aguardando pagamento" do
  Fluxo de Caixa — um atalho pra marcar como paga sem abrir a OS) — **nunca teve**. Só atualizava
  `ordens_servico.situacao_pagamento` e lançava a receita; nem `os_pagamentos` nem a despesa da
  taxa eram gravados, pra NENHUMA forma de pagamento, desde sempre. Confirmado com dados reais:
  a OS problemática não tinha nenhuma linha em `os_pagamentos`, enquanto OS fechadas pela tela
  normal tinham a receita E a despesa lançadas juntas, mesmo timestamp.

**Correção**: `pagarOs()` ganhou a mesma lógica de `fechar()` (cálculo da taxa, insert em
`os_pagamentos`, despesa "Taxa cartão" se `taxa_valor > 0`) — só que sempre 1x sem repasse ao
cliente, porque o modal "Receber OS" não tem seletor de parcelas nem checkbox de repassar taxa
(diferente do fechamento completo da OS, que tem os dois). Se um dia esse atalho ganhar essas
opções na UI, a lógica em `pagarOs()` precisa ser atualizada junto.

**Backfill histórico**: `os_pagamentos` já existia em produção mas **nunca teve migration
commitada** (grep em `database/migrations/*.sql` não acha `CREATE TABLE os_pagamentos` em
lugar nenhum — mesma categoria de gap do `lib/dompdf/vendor/`, algo que existe no VPS fora do
git). Levantamento em produção (2026-08-10) achou 14 OS de várias empresas, pagas em cartão via
esse atalho, sem a despesa da taxa: 2 em débito, 12 em crédito. Só as de **débito** têm conserto
seguro (`scripts/backfill_taxa_cartao_debito.php`, roda em modo simulação por padrão, precisa de
`--aplicar` pra gravar de verdade) — débito não tem parcela, a taxa configurada hoje é uma
estimativa razoável do que valia então. As de **crédito** ficaram de fora de propósito: a taxa
varia muito por número de parcelas (ex.: 1x=3,34%, 12x=12,38%, no caso real observado), e essa
informação não existe em lugar nenhum pras vendas antigas (é exatamente `os_pagamentos` que
faltou gravar) — reconstruir seria adivinhar um valor pra lançar como despesa real de um
cliente, não uma correção.

## Taxa de cartão também cobre Pix (via maquininha)

Pedido real de cliente (Clínica dos Eletros, mesmo dia dos incidentes acima): a maquininha dela
cobra taxa até em Pix (não é o Pix "direto" recebido na conta do banco, que não tem taxa
nenhuma — é especificamente quando o cliente final paga via Pix *pela maquininha de cartão*).
Não existia campo nenhum pra configurar isso, e a primeira versão desta feature aplicava a
taxa em QUALQUER pagamento com `forma_pagamento='pix'` — errado, porque a mesma empresa recebe
pix pelos dois canais e isso cobraria taxa até de pix sem maquininha nenhuma. Corrigido logo em
seguida (mesmo dia) pro desenho abaixo, que é o que vale hoje:

- **Config → Empresa → Cartões** ganhou um campo "Pix (via maquininha)" logo acima do campo
  Débito — mesmo formato (uma taxa única, sem parcela, default 0/vazio = sem taxa). Persistido
  em `configuracoes.taxas_cartao` (JSON) como `pix`, junto de `debito`/`credito`.
  `taxa_cartao_configurada()` (`app/Helpers/functions.php`) ganhou o branch `pix`.
- **`pix_maquininha` é um valor que só existe nos formulários de pagamento** (select de forma
  de pagamento em `os/show.php` — modal Fechar OS —, `pdv/index.php` e o modal "Receber OS" em
  `fluxo_caixa.php`), nunca gravado no banco: é como o usuário sinaliza "este pix específico
  passou pela maquininha". `OrdemServicoController::fechar()`, `PdvController::finalizar()` e
  `FinanceiroController::pagarOs()` normalizam pra `'pix'` assim que leem o POST (antes de
  qualquer cálculo ou insert) — `forma_pagamento` no banco nunca tem outro valor além dos que
  já existiam. Só quando a forma chega como `pix_maquininha` é que `taxa_cartao_configurada()`
  é chamada pra pix; `'pix'` puro nunca dispara o cálculo, mesmo com a taxa configurada > 0.
  Variável antes chamada `$ehCartaoL`/`$ehCartao` renomeada pra `$ehMaquininhaL`/`$ehMaquininha`
  nesses três lugares, já que "é cartão" deixou de ser a pergunta certa.
- Nos selects, "PIX (maquininha)" fica logo abaixo de "PIX" simples — em `os/show.php` e
  `pdv/index.php` (ambos JS-renderizados) escolher essa opção também mostra o campo de taxa
  (somente leitura, populado da config, mesmo padrão de débito/crédito) e ativa a seção
  "quem paga a taxa" (repasse ao cliente), igual cartão.
- Rótulo da despesa gerada é diferente pro Pix (**"Taxa pix (maquininha) — ..."**, sem "débito"
  nem "Nx", que só fazem sentido pra cartão).
- `PdvController` também tem um `$contaSimples` (bucket contábil simplificado) que já tratava
  Pix como `'banco'` separado de `'cartao'` — isso é uma dimensão diferente (de onde o dinheiro
  entra) e não foi tocado; a despesa da taxa em si continua categorizada como `'cartao'`
  (bucket de custo de processamento de pagamento, não literalmente "forma cartão").

## Agenda gera lançamento no Financeiro (opcional)

Pedido do usuário (2026-08-10, mesmo dia dos incidentes de Financeiro acima): fechar o ciclo
entre um evento de agenda tipo=Financeiro (ex.: "Pagamento do Aluguel", recorrente) e o
lançamento de verdade no Fluxo de Caixa — hoje são dois sistemas paralelos sem ligação nenhuma
(nem automática nem manual guiada).

**Achado que definiu o desenho**: `fin_lancamentos.recorrente`/`recorrencia_tipo`/
`grupo_parcela` (migration 001) nunca foram usados por nenhum código — o Financeiro nunca teve
motor de recorrência de verdade. O único motor que existe e funciona é o da Agenda (RRULE,
`app/Helpers/rrule.php`). Por isso a recorrência continua vivendo só na Agenda; o Financeiro
não ganhou (e não deveria ganhar) uma segunda implementação paralela.

**Modelo** (migration `033_agenda_financeiro.sql`, tudo opcional/nullable — evento sem molde
continua sendo só um lembrete, exatamente como "Pagamento do Aluguel"/"Pagamento AP Eliete"
já eram antes desta feature):
- `agenda.fin_tipo` (receita/despesa), `fin_valor`, `fin_categoria_id`, `fin_conta_id` — o
  "molde" de lançamento anexado ao evento. Só aparece no modal quando Tipo=Financeiro
  (`toggleBlocoFinanceiro()` em `agenda/index.php`).
- `fin_lancamentos.agenda_id` — de qual evento/ocorrência aquele lançamento veio. É o que torna
  `AgendaController::marcarPago()` idempotente (não duplica se já existe uma linha com esse
  `agenda_id`).

**Fluxo**: preenchendo o molde, cada ocorrência ganha a ação rápida "Marcar como pago/recebido"
em Próximos 7 dias (só aparece quando `fin_tipo`/`fin_valor` estão preenchidos — mesmo padrão
condicional de Concluir/Cancelar). Clicar cria o lançamento (`status='pago'`,
`data_vencimento`/`data_pagamento` = hoje) e marca o evento como `concluido` — primeiro uso
real do campo `status` da agenda pra esse tipo de evento (fora isso, ele é decorativo/sempre
"agendado", ver mapeamento antigo da tela mais acima neste arquivo).

Ocorrência de série sempre vira exceção ao marcar como paga — reaproveita
`mudarStatusOcorrenciaUnica()` pra resolver/materializar (mesma lógica de "somente este
evento" que Concluir/Cancelar já usam), porque não faz sentido pagar a série inteira de uma
vez só.

**Deliberadamente manual, não automático**: o sistema nunca lança sozinho quando a data chega —
sempre precisa do clique em "Marcar como pago". Motivo: os dois incidentes reais do mesmo dia
(lançamento duplicado por filtro de data, taxa de cartão faltando num atalho) deixaram claro
que postar dinheiro sozinho no financeiro de alguém sem confirmação humana é arriscado — o
valor pode variar (aluguel reajustado) ou o pagamento atrasar. Pode virar automático numa fase
2, depois que esse fluxo provar que é confiável.

**Ainda não implementado** (fase 2 do desenho, não pedida ainda): o sentido inverso — criar um
lançamento recorrente no Financeiro e o sistema montar o evento de agenda por trás sozinho
(reaproveitando `agenda_rrule_montar()`). Por ora, pra ter um lançamento recorrente com
lembrete, o caminho é criar o evento pela própria Agenda.

## Agenda envia dados do atendimento pro técnico/motorista via WhatsApp

Pedido de um usuário: pra um evento de agenda com técnico E OS vinculados (visita/coleta/
entrega), poder mandar pro WhatsApp de quem vai atender — o `usuarios.telefone` do técnico,
mesmo campo que `tecnicos/show.php` já trata como WhatsApp (link `wa.me`) — os dados de quem
ele vai atender, sem precisar abrir o sistema no celular.

- **Botão**: "Enviar dados ao técnico" no menu de ações rápidas de cada evento em "Próximos 7
  dias" (`_proximos7dias.php`) — só aparece quando o evento tem `os_id` E `usuario_id`
  preenchidos (sem OS não tem o que mandar; sem técnico não tem pra quem mandar). Mesmo padrão
  visual/JS dos outros itens desse menu (`agendaAcaoRapidaMarcarPago` etc.), lendo o JSON do
  evento já embutido no `data-evento` do `<ul>` — não abre modal, é 1 clique.
- **`AgendaController::enviarInfoTecnico()`** (`POST /agenda/{id}/enviar-tecnico`) — recebe
  `os_id`/`usuario_id`/`data_inicio`/`titulo` direto do evento que o JS já tem carregado (a
  ocorrência que aparece em "Próximos 7 dias" já é a efetiva, com data resolvida mesmo se for
  série recorrente) — por isso, diferente de `marcarPago()`/`mudarStatus()`, esse endpoint
  **não escreve nada na tabela `agenda`** e não precisa resolver exceção de série/materializar
  ocorrência: é só leitura (OS + técnico) e envio.
- **Duas mensagens pro WhatsApp do técnico**: um texto (cliente, telefone do cliente, endereço
  montado a partir de `clientes.logradouro/numero/bairro/cidade/uf`, aparelho — marca/modelo ou
  tipo se não tiver marca/modelo —, defeito relatado, título e data/hora do evento) seguido do
  **PDF da OS** (reaproveita a view `os.print` + layout `print_orcamento`, o mesmo PDF que já
  vai pro cliente em `OrdemServicoController::enviarPdfWhatsapp()` — não existe um PDF
  "resumido só pro técnico" separado). Usa `WhatsAppService::enviarTexto()` +
  `::enviarDocumento()` pela instância da EMPRESA (`emp_{id}`), então exige o WhatsApp da
  empresa conectado, igual todo outro envio pro cliente.
- **Validações antes de gastar tempo gerando PDF**: técnico existe na empresa, técnico tem
  telefone cadastrado (senão erro claro apontando pra editar em Usuários), WhatsApp da empresa
  conectado — só depois disso monta o PDF.
- Botão fica desabilitado (com spinner) durante o envio, pra evitar clique duplo mandando a
  mesma mensagem/PDF duas vezes — diferente de `marcarPago()`, aqui não tem proteção de
  idempotência no banco (não há registro do envio), então a defesa é só no front.
- **Endereço inclui `clientes.complemento`** (apto/bloco/ponto de referência), além de
  logradouro/número/bairro/cidade/UF — sem isso o técnico podia chegar no prédio certo e não
  achar a unidade certa.

## "Atendimento rápido" — criar evento + avisar técnico em poucos cliques

Pedido do mesmo usuário, na sequência do item acima: o modal completo de evento (~15 campos)
é overkill quando o caso é só "essa OS que já existe precisa de uma visita/coleta/entrega,
bota isso na agenda pro técnico X, nesse horário, e já avisa ele". Criado um atalho paralelo
que não substitui o modal completo — é mais um caminho, pro caso comum.

- **Botão "Atendimento rápido"** na toolbar da Agenda (ao lado de "+ Novo evento"), abre
  `#modalAtendimentoRapido` — só 4 campos: busca (reaproveita `/api/os`, já existente pro campo
  "Ordem de Serviço" do modal completo — aceita número da OS, nome do cliente OU marca/modelo
  do aparelho), Tipo (Ordem de Serviço/Coleta/Entrega), Técnico e Data/hora. Selecionar a OS já
  preenche cliente, mostra um resumo (cliente + aparelho) e sugere o técnico se a OS já tiver
  um responsável (`os.tecnico_id`) — na prática, muitas vezes só falta confirmar horário e
  técnico e clicar em salvar.
- **Um clique faz duas chamadas em cadeia**: `agendaAtendimentoRapidoSalvar()` (JS) primeiro
  `POST /agenda` (o mesmo endpoint do modal completo, `_ajax=1`) pra criar o evento, e — só se
  isso funcionar — emenda automaticamente um `POST /agenda/{id}/enviar-tecnico` (a função da
  seção anterior) com os mesmos dados, sem esperar o usuário achar o evento na lista depois.
  Se a criação falhar, mostra o erro e para aí; se a criação for OK mas o envio ao WhatsApp
  falhar (ex.: técnico sem telefone, WhatsApp desconectado), avisa que o evento foi criado mas
  o aviso não saiu — não desfaz a criação, só informa.
- **`AgendaController::salvar()` ganhou `'id' => $eventoId` na resposta AJAX** (antes só
  devolvia `{sucesso: true}`) — é o que permite ao JS montar a URL do `enviar-tecnico` sem
  precisar recarregar a grade pra descobrir o id do evento recém-criado. Só importa pra esse
  fluxo; os outros callers do endpoint (`formEvento`, `agendaCommitMover()`) ignoram o campo
  extra.
- Não é um endpoint novo, não mexe em recorrência — é só o modal completo simplificado + a ação
  de enviar-tecnico já existente encadeadas via JS.

## Dias padrão da previsão de entrega (configurável)

Pedido do usuário a partir da aba Configurações → Previsão de Entrega: o campo "Previsão de
entrega" do formulário de Nova OS sugeria sempre hoje + 3 dias, fixo no código
(`os/form.php`). Virou configurável por empresa, com **5 dias** de padrão.

- Mesmo padrão chave/valor de `mostrar_previsao` (tabela `configuracoes`, sem migration nova):
  `dias_previsao_padrao`, lido em `OrdemServicoController::criar()` (default 5 se a chave nunca
  foi setada) e usado em `os/form.php` no lugar do `+3 days` fixo (`+{$dias} days`). Só se aplica
  a uma OS NOVA — `editar()` não passa essa variável porque a OS já tem `data_previsao` própria
  (a expressão em `form.php` só cai no default quando `$os['data_previsao']` está vazio).
- **UI**: mesma aba do toggle "Previsão de entrega visível", card novo logo abaixo — campo
  numérico (1–365) + botão "Salvar" próprio, porque diferente do toggle (que salva sozinho ao
  mudar) não faz sentido salvar a cada dígito digitado num campo de número.
- **`DashboardController::salvarPrevisaoConfig()`** (mesma rota `POST /preferencias/previsao`
  de sempre) passou a aceitar os dois campos, `mostrar` e `dias`, cada um só grava/responde
  quando veio no POST — o toggle continua mandando só `mostrar`, o botão novo manda só `dias`,
  sem um pisar no valor do outro. `dias` é clampado 1–365 no servidor (`max(1, min(365, ...))`),
  não só no `min`/`max` do `<input type="number">`, que é só validação de UI.
- É só um valor SUGERIDO no formulário — o usuário sempre pode mudar a data ali na hora; não
  muda nada em OS já existentes nem depois de a OS ser salva.

## Lista de OS: coluna "Entrada" + acessórios obrigatórios na Entrada de Garantia

Dois ajustes pequenos pedidos em sequência na tela `/os`:

- **Coluna "Entrada"** na tabela principal (`os/index.php`), logo depois de "OS" — `data_entrada`
  já vinha na query (`OrdemServico::listar()` usa `SELECT os.*`), só não tinha coluna própria; até
  então só aparecia na linha de detalhe expandida. Removida de lá pra não ficar duplicada na
  mesma tela.
- **Acessórios agora são obrigatórios no passo 3 do modal "Entrada de Garantia"** (revisão do
  equipamento antes de criar a OS de retorno): dava pra clicar em "Criar OS e Imprimir" sem
  selecionar nenhum chip — `gAcessorios` (hidden) ficava `''`, e a OS de garantia era criada sem
  registrar o que o cliente trouxe junto. Bug real, achado testando a tela.
  - **Front**: `confirmarGarantia()` bloqueia o submit se `gSelecionados` estiver vazio, mostra
    `#gAcessoriosErro` e rola até a caixa de seleção — a mensagem orienta a clicar em "Sem
    acessórios" (chip já suportado por `gEhSemAcess()`, é exclusivo com qualquer outro item) se o
    cliente realmente não trouxe nada, em vez de deixar em branco.
  - **Back**: `OrdemServicoController::abrirGarantia()` nunca confia só no JS — `$acessorios`
    trimado, `''` bloqueia com flash de erro antes de criar a OS. Antes disso o default de
    `$this->post('acessorios', ...)` caía pro `acessorios` da OS de ORIGEM quando o campo não
    vinha no POST, o que mascarava o problema (parecia preenchido, mas era herdado, não revisado
    de verdade nesta entrada específica) — agora o default é sempre `''`, força validação.

## Adicionar fotos do estado de entrada direto na tela da OS

Pedido do usuário: o card "Fotos do estado de entrada" em `os/show.php` (ver seção acima sobre
navegação anterior/próxima) era só leitura — as fotos só podiam ser registradas na criação ou no
wizard de edição da OS. Ganhou upload próprio, com dois caminhos, reaproveitando peças que já
existiam:

- **"Adicionar foto"**: `<input type="file" multiple>` comum — comprime cada imagem no navegador
  (canvas, máx. 1280px, JPEG 70%) e manda pro endpoint que já existia,
  `POST /os/{id}/fotos-entrada` (`OrdemServicoController::salvarFotosEntrada()`), o mesmo usado
  pelo botão "Fotografar equipamento" do formulário de OS quando a OS já existe.
- **"Tirar foto pelo celular"**: reaproveita o pareamento por QR já usado no formulário de OS
  (`ScannerController`, modo `fotos_entrada`, endpoints `/scanner/nova` + `/scanner/status` +
  a página pública `/scan/{token}`) — não é uma sessão nova, é o MESMO mecanismo genérico que já
  existia pra "preencher pelo celular", só chamado a partir de uma tela diferente. Se quem está
  na própria tela da OS já é o celular (detecção touch + tela ≤991px, mesma de `form.php`), pula
  o QR — não faz sentido escanear um QR com o mesmo aparelho — e abre a câmera direto.
- Depois de salvar (por qualquer um dos dois caminhos), a tela recarrega pra mostrar as fotos
  novas nas miniaturas e no modal de navegação — sem tentar re-renderizar a grade via JS
  duplicando a lógica que já existe em PHP.
- `salvarFotosEntrada()` já mandava uma mensagem de WhatsApp pro cliente avisando que novas
  fotos foram registradas (quando a OS tem link de acompanhamento e o WhatsApp da empresa está
  conectado) — esse comportamento não mudou, só passou a valer também pra fotos adicionadas por
  aqui, não só pelas do formulário.

## Bug: OS recusada aparecia em "OS aguardando pagamento" no Fluxo de Caixa

Reportado pelo usuário com print da tela: OS fechadas como "Recusado"/"Sem Conserto" apareciam
no quadro "OS aguardando pagamento" do Fluxo de Caixa, com botão "Receber" — como se tivessem
dinheiro de verdade pendente, quando na verdade o orçamento foi recusado e não há nada a
cobrar.

**Causa**: `Financeiro::osPendentes()` filtrava só por `s.tipo IN ('concluida','entregue')` +
`situacao_pagamento` + `valor_total > 0`, sem checar `fechada_sem_receita` — a mesma regra que
`OrdemServico::listar()` já aplica no total da lista de OS (comentário lá: "'Sem Conserto/
Recusado' nunca conta no total, mesmo com valor_total preenchido"). Ficou inconsistente: um
lugar aplicava a regra, o outro não. Corrigido com `COALESCE(os.fechada_sem_receita, 0) = 0` na
query (não `!= 1` puro, porque linhas antigas podem ter a coluna `NULL`, e `NULL != 1` em SQL
não é `TRUE` — excluiria OS válidas do resultado).

## Adiantamento de OS

Pedido do usuário: peça cara, o dono da assistência pede um sinal adiantado ao cliente antes de
comprar/consertar — precisava de um jeito de registrar isso com "as mesmas regras financeiras"
já usadas no fechamento da OS (forma de pagamento, taxa de cartão configurada, repasse da taxa
ao cliente). Card novo "Adiantamento" em `os/show.php`, entre "Peças utilizadas" e "Laudo
técnico", mesmo padrão visual de lista + "+ Adicionar" das outras duas.

- **Migration `034_os_adiantamentos.sql`** — tabela dedicada (`os_adiantamentos`), não reaproveita
  `os_pagamentos` (que é especificamente do fechamento, ver `fechar()`) nem `agenda`. Guarda
  `fin_lancamento_id`/`fin_lancamento_taxa_id` (ambos `ON DELETE SET NULL`) — é o que permite
  `excluirAdiantamento()` estornar exatamente os lançamentos que aquele adiantamento gerou, sem
  precisar adivinhar por descrição/valor.
- **`OrdemServicoController::adicionarAdiantamento()`** — só permitido com a OS ainda não
  fechada (`status_tipo != 'entregue'`). Mesma normalização `pix_maquininha` → `pix`, mesma
  `taxa_cartao_configurada()`, mesmo cálculo de `valor_cobrado` (repasse ao cliente) e
  `taxa_valor` que `fechar()` já usa — mesma categoria "Serviços"/"Taxas de cartão" no
  Financeiro. Gera a receita (e a despesa da taxa, se houver) **na hora**, não espera o
  fechamento — é dinheiro que já entrou de verdade.
- **Efeito colateral que exigiu mexer em `fechar()`**: antes, o guard de idempotência do
  fechamento (`"já lancei o financeiro desta OS?"`) checava `fin_lancamentos WHERE os_id=? AND
  tipo='receita'` — com um adiantamento já tendo criado essa mesma linha antes, o fechamento
  achava que já tinha rodado e **pulava silenciosamente** o lançamento da parte restante paga
  no fechamento. Trocado pra checar `os_pagamentos` (só escrito pelo bloco do fechamento em si,
  nunca por adiantamento) — guard volta a significar o que deveria.
- **`valor_pago`/`situacao_pagamento` agora acumulam, não sobrescrevem**: `fechar()` somava só o
  valor pago NAQUELE fechamento; se a OS já tinha um adiantamento, isso apagava o valor
  anterior. Agora soma `$os['valor_pago']` (o que já tinha) + o que está sendo recebido agora.
  Fechamento "Sem Conserto/Recusado" não zera mais um adiantamento genuíno recebido antes — só
  não soma nada novo (a recusa em si não gera cobrança, mas não estorna sozinha um dinheiro que
  já entrou; estornar de verdade é decisão de fora do sistema).
- **Reaproveita a UI que já existia em vez de recriar**: o card lateral "Financeiro" (Pago/Saldo)
  já lia `os['valor_pago']` — nada mudou lá, o adiantamento só passou a alimentar o mesmo campo.
  O modal "Fechar OS" ganhou um aviso "Já adiantado: R$X / Falta receber: R$Y" (só aparece
  quando `valor_pago > 0`) pra quem fecha saber que deve cobrar só a diferença — o valor a
  cobrar continua sendo digitado manualmente, o sistema não tenta pré-preencher/subtrair
  sozinho (o formulário de fechamento já tem lógica própria de split de pagamento; alterar isso
  também tinha risco maior do que o pedido pedia).
- **Excluir um adiantamento estorna de verdade**: apaga os `fin_lancamentos` vinculados (receita
  + taxa, se houver) e desconta de `valor_pago`, recalculando `situacao_pagamento`. Só aparece
  o botão de excluir com a OS ainda aberta, mesmo critério do "+ Adicionar".

## Excluir foto do estado de entrada — só admin

Pedido do usuário: no card "Fotos do estado de entrada" (`os/show.php`), botão de excluir (✕
vermelho sobre a miniatura) visível só pra admin (`\App\Core\Auth::isAdmin()`).

- **Endpoint compartilhado**: `OrdemServicoController::excluirFotoEntrada()` já existia e já era
  usado pelo botão de remover do wizard de edição (`os/form.php`) — não criei um endpoint novo,
  só acrescentei a checagem `Auth::isAdmin()` nele. Como é o MESMO endpoint pros dois lugares, a
  regra passou a valer nos dois — o botão de remover em `form.php` também ficou escondido pra
  não-admin (senão apareceria um botão que falha ao clicar, em vez de simplesmente não aparecer).
- **Dupla camada**: o botão só renderiza pra admin (`Auth::isAdmin()` na view), e o servidor
  também rejeita com 403 se não for admin — adulterar o HTML pra fazer aparecer o botão não
  contorna a regra.

## Bug: usuário sem `atende_os` aparecia como "Técnico" numa OS

Reportado pelo usuário com print: uma OS mostrava um usuário (Gabriel Barbosa) como "Técnico"
mesmo ele não aparecendo na lista "Equipe de Técnicos" (Config → Técnicos), que só lista
`perfil='tecnico'` OU `atende_os=1` (`TecnicoController::index()`). O `<select>` do formulário de
OS (`os/form.php`) já é populado só com `Usuario::tecnicos()` — mesmo critério —, então em tese
é impossível escolher alguém assim pela UI atual; mais provável é o usuário ter tido
`atende_os=1` em algum momento (e depois desmarcado) e a OS ter guardado esse `tecnico_id` de
quando isso era válido.

De qualquer forma, o backend nunca validava o `tecnico_id` recebido — confiava cegamente no que
vinha no POST (`$this->post('tecnico_id') ?: null`), então qualquer id, de qualquer usuário da
empresa, era aceito, guarde ou não relação com a lista real de técnicos. Corrigido com
`OrdemServicoController::validarTecnicoId()` (mesmo critério de `Usuario::tecnicos()`: ativo +
`perfil='tecnico'` OU `atende_os=1`), aplicado em `salvar()` (criar OS), `atualizar()` (editar) e
`abrirGarantia()` (com fallback pro técnico da OS original, também validado). Um `tecnico_id`
inválido agora vira `NULL` ("Sem técnico") em vez de ser salvo do jeito que veio.

**Não corrige dados já salvos** — não tenho acesso ao banco de produção. Pra essa OS específica
(e qualquer outra na mesma situação), abrir "Editar" e salvar de novo já resolve sozinho: como
Gabriel não está na lista de técnicos, o `<select>` cai automaticamente em "Sem técnico" (não
tem `<option>` dele pra selecionar), então um simples "Editar → Salvar" sem mexer em mais nada
já limpa o campo.

## Catálogo de "Serviços cadastrados"

Pedido do usuário a partir de um print do card "Serviços realizados" da OS mostrando uma
descrição digitada errada ("jhivh") — o campo Descrição do modal "Novo Serviço" sempre foi
texto livre, sem padronização nenhuma (diferente de Peças, que sempre teve o catálogo de
`produtos`). Resposta: um catálogo de serviços análogo ao de produtos, mas propositalmente mais
simples (sem estoque/fornecedor — serviço não tem quantidade física a controlar).

- **Migration `035_servicos_catalogo.sql`** — tabela nova `servicos_catalogo`
  (`empresa_id, descricao, valor_padrao, ativo`), sem FK de `os_servicos` apontando pra ela:
  `os_servicos.descricao` continua sendo `VARCHAR` livre (arquitetura decidida deliberadamente,
  ver abaixo) — o catálogo é só a FONTE do autocomplete, não uma relação obrigatória.
- **`ServicosCatalogoController`** (`app/Controllers/ServicosCatalogoController.php`) — mesmo
  padrão simples de `ProdutoCategoriasController` (sem Model dedicado, `DB::pdo()` direto):
  `index()`/`salvar()`/`atualizar()`/`excluir()` (soft delete via `ativo=0` — mantém histórico,
  e como não há FK, não existe órfão de `os_servicos` pra desvincular) + `buscarAjax()`
  (`GET /api/servicos?q=`, usado só pelo autocomplete da OS).
- **Tela `/servicos`** (`app/Views/servicos/index.php`) — card "Novo serviço" (Descrição +
  Valor padrão) ao lado da tabela com editar (modal)/excluir, visual idêntico a
  `produtos/categorias.php`. Permissão reaproveita o módulo `estoque` (`Auth::can('estoque',
  ...)`) — mesma área conceitual de "catálogo do que a empresa vende/usa" — e
  `Auth::moduloDoUri()` ganhou o prefixo `/servicos => estoque` pra rota ficar sob controle de
  acesso por papel (sem isso, `AuthMiddleware` não teria como restringir a tela).
- **Sidebar**: link simples "Serviços" (`bi-tools`) logo abaixo do grupo "Produtos e estoque",
  mesmo nível de "Clientes" — não virou um grupo com sub-itens porque é só uma tela (diferente
  de Produtos, que tem Produtos/Categorias/Fornecedores).
- **Autocomplete no modal "Novo Serviço" da OS** (`os/show.php`) — mesmo padrão de debounce +
  `list-group` posicionado absoluto já usado no PDV (`pdv/index.php`, busca de produto/cliente):
  digitar no campo Descrição busca em `/api/servicos`, selecionar preenche Descrição e Valor
  Unitário (só se o serviço tiver `valor_padrao > 0`) — mas o campo continua sendo texto livre,
  digitar algo fora do catálogo é permitido normalmente (nem todo serviço prestado é
  recorrente o bastante pra valer cadastrar).
- **Decisão deliberada: sem FK / sem migração de dados antigos.** `os_servicos.descricao`
  segue string solta — criar uma FK obrigaria migrar todo o histórico existente (descrições
  livres não batem 1:1 com nada) e complicaria o INSERT de um serviço avulso digitado na hora.
  O catálogo é puramente um atalho de digitação; nada impede duas OS terem a mesma descrição
  vindas de fontes diferentes (uma do catálogo, outra digitada igual à mão).

## Recibo de Adiantamento — comprovante individual por WhatsApp

Pedido do usuário logo depois de testar a feature de Adiantamento: nenhum documento provava,
pro cliente, que ele tinha adiantado dinheiro — só existia o registro interno na tela da OS. Ao
contrário dos outros documentos de impressão (orçamento, fechamento, garantia...), que são sobre
a OS inteira, este é **por lançamento** — cada linha da tabela de Adiantamento tem seu próprio
recibo, já que cada uma é um pagamento separado (data/forma/valor próprios).

- **`OrdemServicoController::imprimirAdiantamento($id, $adiantamentoId)`** (`GET
  /os/{id}/adiantamentos/{itemId}/imprimir`) e **`enviarAdiantamentoWhatsapp($id,
  $adiantamentoId)`** (`POST .../whatsapp`) — mesmo padrão dos outros `imprimir*`/
  `enviarPdfWhatsapp` já existentes (`saidaImpressao()` pra HTML ou PDF via `?pdf=1` com
  Dompdf, `WhatsAppService::enviarDocumento()` pra mandar o PDF pelo número da empresa). Não
  reaproveita o `$map` genérico de `enviarPdfWhatsapp()` porque esse endpoint é sobre a OS
  inteira (um `tipo` fixo por chamada) — adiantamento precisa também do id do lançamento
  específico, por isso ganhou métodos próprios em vez de mais uma entrada no `$map`.
- **`app/Views/layouts/print_adiantamento.php`** — layout autocontido (mesmo padrão de
  `print_fechamento.php`: a "view" pareada em `os/print_adiantamento.php` é só um stub vazio,
  já que esses layouts de impressão nunca chamam `$content()` — todo o HTML já está no próprio
  layout). Cabeçalho da empresa (logo/CNPJ/endereço) igual aos outros documentos, um bloco de
  destaque com o valor recebido + forma de pagamento, texto de declaração ("recebemos de
  [cliente] a quantia de R$X a título de adiantamento/sinal referente à OS nº Y... será abatido
  do total no fechamento") e assinaturas — sem repetir a lista de serviços/peças da OS, que não
  é o assunto deste recibo.
- **Botão de imprimir em cada linha da tabela de Adiantamento** (`os/show.php`) — ícone de
  impressora, visível **sempre** (mesmo com a OS já fechada, diferente do botão de excluir que
  some quando `!$podeFechar`), já que reimprimir/reenviar o comprovante de um pagamento já feito
  continua fazendo sentido depois do fechamento.
- **Botão "Enviar por WhatsApp" não reaproveita `_botao_wa_pdf.php`** (o partial genérico usado
  nos outros documentos) — esse partial só manda `tipo` pro endpoint genérico da OS inteira, sem
  como informar QUAL adiantamento. Em vez disso, um script inline pequeno no próprio layout
  chama o endpoint dedicado (`.../adiantamentos/{itemId}/whatsapp`), mesmo padrão de estado do
  botão (desabilita durante envio, mostra ✓ Enviado ou erro).

## Bug relatado por cliente: câmera não abre em tempo real no Android (scanner de etiqueta via QR)

Cliente real (empresa com e-mail eletrolisp@gmai.com) reportou dois problemas no fluxo de
cadastro de equipamento por foto — ambos corrigidos nesta rodada.

**Corrigido**: `app/Views/scanner/pagina.php` (página que abre no CELULAR depois de escanear o
QR Code pra fotografar a etiqueta/placa) tinha `<input type="file" accept="image/*">` **sem**
`capture="environment"`. Sem esse atributo, o Android abre o seletor de arquivo genérico (Fotos/
Arquivos/Câmera como opções, geralmente com a galeria em destaque) em vez de abrir a câmera
direto — no iOS Safari isso normalmente ainda mostra "Tirar Foto" com destaque, o que mascarava
o problema (só reproduz no Android, como o cliente relatou). Comparando com os outros dois
pontos que já tiravam foto direto no celular (`#inputCameraDireta` em `os/form.php`, e os
inputs de `scanner/fotos_entrada.php`/`fotos_whatsapp.php`), só esse estava sem o atributo —
inconsistência entre os três lugares que fazem a mesma coisa. Adicionado `capture="environment"`.

**Não mexido nesta rodada**: `scanner/fotos_entrada.php` e `scanner/fotos_whatsapp.php` têm
`capture="environment"` **junto com** `multiple` no mesmo `<input>` — combinação que em vários
Android/Chrome faz o `capture` ser ignorado (o SO trata como conflito de intenção: captura única
vs. seleção múltipla) e cai de novo no seletor genérico. Não foi reportado como bug pelo cliente
nesta rodada (ele só mencionou o fluxo de etiqueta, que é foto única), e mexer nisso — dá pra
tirar várias fotos numa sequência de capturas ao invés de um único input `multiple` — é uma
mudança de fluxo maior, não só adicionar um atributo. Vale investigar se isso também for reportado.

## Bug relatado pelo mesmo cliente: OS "voltava pro cadastro" ao escanear a etiqueta

Segundo problema relatado pela mesma empresa (eletrolisp@gmai.com), intermitente ("às vezes"),
também só em Android — sem print, porque não reproduzia sob demanda. Não deu pra confirmar 100%
sem um Android real (não tenho acesso a um), mas a explicação mais consistente com os 3 sintomas
relatados (aleatório + só Android + volta pro formulário em branco) é o Android **matar/recarregar
a aba do navegador** quando ela vai pro segundo plano — o que acontece exatamente ao abrir a câmera
do sistema pra fotografar a etiqueta (`abrirCameraDireta()` em `os/form.php`, usado quando o
próprio celular preenche a OS, sem QR). Sob pressão de memória (mais comum em aparelhos com menos
RAM), a Chrome do Android recarrega a aba do zero ao voltar da câmera — isso apaga **todo o estado
em JavaScript** (cliente selecionado, campos do equipamento lidos pela IA, a etapa do wizard em
que a pessoa estava), e a OS aparece de volta como um formulário novo em branco.

O sistema já tinha um mecanismo de "rascunho" (`localStorage`) pra esse tipo de acidente, mas ele
só cobria 3 campos de texto (`defeito_relatado`, `observacoes_cliente`, `observacoes_internas`) —
não protegia nem o cliente selecionado nem os dados do equipamento, exatamente o que se perde
nesse fluxo. Estendido (`app/Views/os/form.php`, IIFE "autosave de rascunho", só em OS nova):

- **`window._salvarRascunhoOS(extra)`** — função compartilhada que lê o rascunho atual do
  `localStorage`, faz merge com `extra` (em vez de sobrescrever) e regrava. Exposta em `window`
  porque é chamada de dois pontos fora da IIFE original: `preencherDoScanner()` (salva
  `equip_tipo/marca/modelo/serie` assim que a IA lê a etiqueta — o ponto mais crítico, ANTES do
  clique em "Confirmar equipamento") e `confirmarClienteEAbrirEquip()` (salva o cliente
  escolhido). Os saves são idempotentes — chamar de novo com os mesmos dados não tem efeito
  colateral, então não tem problema salvar tanto no fluxo normal quanto durante uma restauração.
- **Restaurar reaproveita as funções existentes** em vez de duplicar lógica: em vez de escrever
  os campos escondidos (`fEquipTipo` etc.) e o card de resumo na mão, o clique em "Restaurar"
  chama `selecionarCliente(...)` (já faz tudo: seleciona o cliente, atualiza a UI, abre o modal
  de equipamento, avança pro step 1) e, 500ms depois — tempo do modal abrir —, chama
  `preencherDoScanner(...)` (a MESMA função que o scanner usa) pra preencher os campos visíveis
  do modal. O usuário só precisa clicar em "Confirmar equipamento" de novo — recupera exatamente
  o ponto onde parou, em vez de perder tudo e escanear de novo.
- **Não elimina a causa raiz** (o Android matando a aba não é algo que dá pra evitar do lado do
  app), só garante que o trabalho não se perde quando acontece. Não foi possível testar num
  Android real — validado só via `node --check` na sintaxe JS e revisão manual da lógica.

**Confirmado pelo cliente**: o fix da câmera (seção acima) resolveu os dois problemas — o cliente
notou que o segundo bug (voltar pro cadastro) só acontecia em Android, nunca em iOS, e acredita
que era o MESMO problema raiz: sem `capture="environment"`, o Android abria o seletor de arquivo
genérico (mais pesado, mais navegação) em vez da câmera direto — mais tempo com a aba em segundo
plano, mais chance do sistema reciclá-la por pressão de memória. A extensão do rascunho continua
valendo como rede de segurança pra quando isso acontecer de novo, mesmo mais raro agora.

**Varredura preventiva pedida pelo cliente** ("pra outras [empresas] não apontem defeitos
semelhantes"): sem acesso ao banco de produção pra revisar a conta dela, a alternativa foi
buscar no código TODOS os `<input type="file">` do sistema atrás do mesmo padrão (câmera anunciada
mas sem `capture`). Comparado cada um contra o texto/ícone ao lado (indício de intenção de câmera)
e contra o texto de marketing da landing ("Cadastre produtos e anúncios com foto tirada na hora
pela câmera do celular" — `landing/index.php`, feature "Fotos de produtos e peças"). Achados e
corrigidos (mesmo padrão do bug, mesma correção):
- `produtos/form.php` (`inputFotoProd`) — botão dizia literalmente "Tirar foto / escolher".
- `marketplace/meus_anuncios.php` (`imagem_principal`, novo anúncio) e `marketplace/editar.php`
  (`imagem_principal`, editar anúncio) — cobertos pela promessa da landing acima, mesmo sem texto
  "tirar foto" ao lado do campo em si.

**Deliberadamente NÃO mexido** (campos `type="file"` que ficaram de fora, com o motivo de cada
um): `galeria[]` em `meus_anuncios.php`/`editar.php` (tem `multiple` — mesmo conflito
`capture`+`multiple` já documentado acima, adicionar não funcionaria de forma confiável);
`empresa/perfil_publico.php` (logo, foto de capa, galeria de fotos do perfil), `empresa/index.php`
(logo) — são arquivos que a empresa já TEM prontos (logo, banner), não algo que faz sentido
fotografar na hora; `empresa/migracao_shoficina.php` (upload de `.sql`), `empresa/
diretorio_anuncios.php` (comprovante de pagamento, banner de anúncio — normalmente já existem como
arquivo/print, não é algo pra fotografar ao vivo); `forum/topico.php` (anexos genéricos);
`editor_imagens/index.php` e `imagem/editor.php` (ferramentas de EDITAR uma imagem já existente —
forçar câmera aqui removeria a opção de escolher o arquivo que a pessoa quer editar, o oposto do
que a ferramenta faz); `os/show.php`/`os/form.php` campos `inputFotosEntrada`/`feInputArquivo` —
esses são deliberadamente o botão "escolher arquivo" que complementa o botão separado "Tirar foto
pelo celular" (que já usa QR code + `scanner/pagina.php`, já corrigido) — dar capture nesses
removeria a opção de escolher uma foto já existente, que é exatamente o propósito desse botão.

## Auditoria de SEO do Diretório (`/assistencias`)

Pedido do usuário: revisar se o diretório está "bem otimizado" — não tenho acesso a analytics/
Search Console/produção, então foi uma auditoria só de código. Achado principal: `diretorio/
empresa.php` e `diretorio/encontrar.php` tinham seu próprio `<title>`/`<meta description>`/
`<link rel="canonical">`/`og:*` **duplicados e no lugar errado**. O layout `landing.php` só chama
`($content)()` na linha ~246, bem depois de `</head>` — ou seja, essas tags "duplicadas" estavam
sendo emitidas dentro do `<body>`, onde navegador/crawler não lê `<title>`/meta/canonical (JSON-LD
é a exceção — é válido em qualquer lugar do documento, por isso ficou).

- **Efeito prático mais sério**: a página de empresa tentava usar a foto real da fachada
  (`$empresa['foto_capa']`) como `og:image`, mas por estar no body isso nunca era lido — todo
  link de assistência compartilhado no WhatsApp mostrava só o ícone genérico do FixaOS (no
  `<head>`, hardcoded). Corrigido dando ao layout a capacidade de receber `$ogImage`/`$canonical`
  por página (`app/Views/layouts/landing.php`) e ao `DiretorioController::empresa()` passar a
  foto de capa real + o canonical correto via `compact()`. Removidas as tags duplicadas das duas
  views (`empresa.php`, `encontrar.php`) — só ficou o JSON-LD, que agora reaproveita
  `$tituloFull`/`$metaDesc` (do controller) em vez de recalcular um texto genérico próprio que
  silenciosamente sobrescrevia o do controller sem efeito nenhum no `<head>`.
- **JSON-LD de `encontrar.php` trocado pra `json_encode()`** — antes interpolava a variável direto
  na string JSON sem escapar corretamente (usava a mesma técnica de escape de HTML, errada pra
  esse contexto); agora usa `json_encode(..., JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)`.
- **Código morto removido**: `DiretorioController::index()` (nunca tinha rota — `/assistencias`
  vai pro método `encontrar()`, não `index()`) e a view associada `diretorio/index.php` (20KB,
  só usada por esse método morto). O método também tinha uma query de `COUNT(*)` duplicada sem
  efeito nenhum (resultado descartado e recalculado na linha seguinte).
- **`sitemap.xml` não lista mais `/encontrar`** — essa rota é só um redirect 301 pra
  `/assistencias` (`DiretorioController::encontrarLegado()`, mantido pra não quebrar links
  antigos); `/assistencias` já está listado no sitemap, então submeter a URL que redireciona era
  redundante e podia gerar aviso de "página com redirecionamento" no Search Console.
- **Gap identificado mas NÃO mexido** (fora do escopo do que foi pedido): `encontrar()` marca
  `noindex` em qualquer busca filtrada/paginada — só `/assistencias` puro é indexável. Não existe
  hoje nenhuma página tipo "assistência técnica em Campinas" que o Google possa indexar
  diretamente; todo o peso de SEO local recai só sobre os perfis individuais de empresa. Resolver
  isso exigiria páginas dedicadas por cidade/estado (ex.: `/assistencias/sp/campinas`), uma
  mudança estrutural maior que não foi pedida nesta rodada.

## Seed de dados fictícios pra vídeos institucionais

Pedido do usuário: popular a empresa FixaOS (tenant já cadastrado, usado como demo/vitrine do
próprio sistema) com clientes, produtos e ~1000 OS fictícios espalhados em mais de um ano, pra
gravar vídeos institucionais pro YouTube com telas/dashboards/relatórios parecendo uma operação
real em vez de uma conta vazia.

`scripts/seed_dados_demo.php` — mesmo padrão dos outros scripts de `scripts/` (roda em modo
SIMULAÇÃO por padrão, só grava com `--aplicar`). Resolve a empresa por `nome_fantasia`/
`razao_social LIKE '%FixaOS%'` (ou `--empresa=ID` explícito) e **lê o que a empresa já tem**
em vez de recriar — `os_status`, `categorias_equipamento` e técnicos (`perfil='tecnico' OR
atende_os=1`) já existem desde o cadastro (seed padrão em `LandingController`), então o script
só consulta. Nunca gera número de OS abaixo do maior já existente na empresa (evita colidir com
a constraint única `uq_os_numero_empresa`) — por padrão pede pra começar em 145, mas sobe
sozinho se já houver OS além disso.

Cada uma das ~1000 OS tem categoria de equipamento (peso realista: celular 28%, notebook 14%,
TV 13% etc.), marca/modelo, defeito, 1-2 serviços e 0-2 peças vindos de pools por categoria
(ex.: "Troca de tela" + peça "Tela Samsung Galaxy A54" só aparecem em OS de celular), com
`valor_total`/`situacao_pagamento`/`valor_pago` calculados a partir disso. Datas geradas em uma
linha do tempo de ~13 meses até agora, em horário comercial; o **status é sorteado com peso
pela idade da OS** — OS com mais de 10 dias fica majoritariamente "entregue" (~76%) ou
"cancelada" (~13%), OS bem recente fica majoritariamente aberta/em andamento — pra parecer uma
operação madura com histórico real, não um monte de OS abertas do nada. OS "cancelada" tem 50%
de chance de ser um orçamento recusado sem cobrança nenhuma (`fechada_sem_receita=1`, só se a
coluna existir — feature-detectada via `SHOW COLUMNS`, já que não está nas migrations
versionadas, ver "Bug: OS recusada aparecia em..." mais acima) e 50% de ter cobrado só a taxa
de diagnóstico.

Cria também ~55 produtos (peças, prefixo `codigo LIKE 'DEMO-%'`) e ~30 entradas em
`servicos_catalogo` (prefixo `descricao LIKE 'DEMO: %'`) reaproveitados entre as OS geradas, e
~280 clientes fictícios (`tags='seed-demo'`, nomes/telefones/e-mails gerados, nenhum dado real)
com `criado_em` coerente com a data da primeira OS de cada um — e distribuição de repetição
(alguns clientes aparecem em várias OS, simulando cliente recorrente) via um pool ponderado.
**Deliberadamente não mexe no Financeiro** (`fin_lancamentos`) — replicar a lógica de fechamento
de OS/taxa de cartão só pra dado fictício seria complexidade desproporcional ao pedido; os
valores ficam só em `ordens_servico.valor_total/valor_pago`, suficiente pra listas e dashboards
de OS aparecerem povoados nos vídeos.

**Testado antes de liberar pro VPS**: rodado de ponta a ponta (todos os 1000 registros) contra
um SQLite em memória com schema equivalente ao de produção — sem usar nenhuma linha de dado
real, só validando que o script completa sem erro e gera valores/distribuição plausíveis — já
que não há banco de teste no projeto (mesma limitação de sempre, ver "Stack e comandos").

O comentário no topo do script tem os `DELETE` pra desfazer tudo depois (clientes por
`tags='seed-demo'`, produtos/serviços por prefixo `DEMO-`/`DEMO:`, OS pela faixa de número —
equipamentos/os_servicos/os_pecas somem sozinhos via `ON DELETE CASCADE`).

## Comprovante de venda do PDV em A4 + envio por WhatsApp

O comprovante do PDV (`pdv/comprovante.php`) só existia em formato cupom estreito (340px,
pensado pra impressora térmica) — sem logo, sem endereço completo da empresa, e sem jeito de
mandar pro cliente pelo WhatsApp. Pedido do usuário: uma versão A4 pra impressora comum, com
cabeçalho de verdade, e envio por WhatsApp via API.

Seguiu o mesmo padrão já usado nos documentos de impressão da OS (`layouts/print_orcamento.php`,
`layouts/print_adiantamento.php` etc.), não um mecanismo novo:
- **`layouts/print_venda_pdv.php`** (novo, autocontido) — cabeçalho com logo + razão social/CNPJ/
  endereço/telefone/WhatsApp da empresa, dados do cliente, tabela de itens, total (com desconto
  se houver), forma de pagamento (+ recebido/troco se dinheiro), observações. Barra `.no-print`
  com "Imprimir/Salvar PDF", "Enviar por WhatsApp" e "Voltar". Suporta `?pdf=1` (Dompdf via
  `PdfService`), igual os outros documentos.
- **`pdv/print_venda.php`** (novo) — stub vazio pareado com o layout acima, mesmo padrão de
  `print_adiantamento.php`/`print_fechamento.php` (o layout nunca chama `($content)()`).
- **`PdvController::buscarVendaCompleta()`** — extraída de `comprovante()` e reaproveitada pelas
  duas rotas novas (`imprimirA4()`, `enviarComprovanteWhatsapp()`); passou a buscar também
  `clientes.whatsapp` e `empresas.whatsapp`, que a query antiga do cupom não trazia (só tinha
  `telefone`). `enviarComprovanteWhatsapp()` gera o PDF e manda via
  `WhatsAppService::enviarDocumento()` (Evolution API da empresa), com as mesmas checagens dos
  outros envios (CSRF, WhatsApp da empresa conectado, cliente com whatsapp/telefone cadastrado —
  senão erro claro em vez de tentar enviar pra ninguém).
- Rotas: `GET /pdv/comprovante/{id}/a4`, `POST /pdv/comprovante/{id}/whatsapp`.
- **Botão "Imprimir A4 / WhatsApp"** na barra do cupom térmico, linkando pra tela nova — a barra
  virou grade 2x2 (`flex-wrap`) pra caber os 4 botões nos mesmos 340px de largura do cupom, sem
  alargar a página.

Testado renderizando as duas views com dados fictícios (sem depender do banco) e conferindo
visualmente via Playwright antes de liberar pro VPS — mesma prática de outras telas visuais
neste projeto (não há teste automatizado de tela nenhum).

## Garantia do produto (obrigatória) — aplicada automaticamente no PDV

Pedido do usuário: campo de garantia (em dias) no cadastro de produto, obrigatório, que também
valha na venda pelo PDV.

- **Migration `036_produtos_garantia.sql`** — `produtos.garantia_dias` (SMALLINT UNSIGNED NOT
  NULL DEFAULT 90), mesmo padrão de nome já usado em `ordens_servico.garantia_dias`. Default 90
  pros produtos já cadastrados (retroativo); `0` é um valor válido explícito ("sem garantia") —
  só o campo vazio ou negativo é rejeitado.
- **Dupla validação, mesmo padrão dos outros campos obrigatórios do projeto**: `required` no
  HTML (`produtos/form.php`, seção Estoque e Preços) é só a primeira barreira — o servidor
  (`ProdutoController::salvar()`/`atualizar()`) confere de novo e rejeita com `backWithInput()`
  (preserva o que já foi digitado) se vier vazio ou negativo, porque um POST direto ignora
  `required`.
- **`Produto::buscar()`** (usado pelo autocomplete de produto do PDV, `GET /api/produtos`)
  passou a trazer `garantia_dias` junto.
- **`pdv/index.php`, `addProduto()`** — ao adicionar um produto ao carrinho, a descrição do item
  já soma `"— garantia de N dias"` automaticamente (`descricaoComGarantia()`), então o
  comprovante (térmico e A4) e o financeiro já saem com a garantia registrada, sem precisar
  digitar nada na hora da venda. Garantia `0` não soma texto nenhum.

## PDV: modal de item avulso (produto não cadastrado)

O botão "Adicionar item avulso" (`pdv/index.php`) usava dois `prompt()` do navegador (descrição,
depois valor) — trocado por modal (`#modalItemAvulso`, mesmo padrão Bootstrap do
`#modalNovoClientePdv` já existente na mesma tela) a pedido do usuário, ganhando também campo de
garantia e um campo livre "pra quem é".

- **Campos**: Descrição (obrigatório), "Pra quem é" (opcional, texto livre), Garantia em dias
  (opcional, número) e Preço unitário (obrigatório). Diferente da garantia de produto cadastrado
  (`produtos.garantia_dias`, obrigatória, ver seção acima), aqui é opcional — item avulso não tem
  linha na tabela `produtos` pra guardar isso, então não existe "obrigar" de verdade, só um campo
  a mais na descrição se preenchido.
- **`descricaoItemAvulso()`** reaproveita `descricaoComGarantia()` (mesma função que já monta o
  texto de garantia pra produto cadastrado) e soma por cima `" (pra: Fulano)"` se o campo "pra
  quem é" vier preenchido — os itens do carrinho (`carrinho.push(...)`) continuam sendo só
  `{produto_id, descricao, quantidade, valor_unitario, estoque}`; não há coluna nova no banco
  nem mudança no `PdvController::finalizar()` — o "pra quem" vira só texto dentro da mesma
  `descricao` que já ia pro comprovante, sem exigir schema novo.
- **Deliberadamente separado do campo "Cliente"** do painel de Pagamento (que é da venda
  inteira, um só por venda) — o "pra quem é" do item avulso é por item, pra anotar destinatário
  de um item específico dentro de uma venda com vários itens, sem se misturar com o cliente que
  está pagando.
- Botão "Adicionar item avulso" também ficou maior e colorido (`btn btn-primary`, sem
  `btn-sm`/`btn-outline-secondary`) — antes era um botão pequeno e discreto, destoando de ser a
  única forma de vender algo fora do catálogo.

## Bug: PDV gravava "Dinheiro" mesmo com outra forma de pagamento selecionada

Reportado pelo usuário com print do comprovante: uma venda com item avulso mostrou "Pagamento:
Dinheiro" mesmo sem o usuário ter necessariamente escolhido dinheiro.

**Causa**: `pdv/index.php` nunca pré-preenchia o campo "Valor" da linha de pagamento — o usuário
trocava a forma no `<select>` (ex.: PIX) mas, se esquecesse de digitar o valor (achando que só
selecionar a forma já bastava, já que é a única linha e cobre a venda inteira), o campo ficava
`''`. No envio, `pagamentosEnvio` filtra linhas com `valor > 0`
(`linhasPag.filter(l => l.forma && num(l.valor) > 0)`), então essa linha era descartada e o
`fetch` mandava `pagamentos: []`. `PdvController::finalizar()` trata `pagamentos` vazio como "só
uma forma, formato antigo" e lê `$this->post('forma_pagamento', 'dinheiro')` — campo que o
front-end **nunca envia** (só manda `pagamentos` como JSON) — então sempre caía no default
`'dinheiro'`, silenciosamente, pro valor total da venda. `btnFinalizar` também não bloqueava o
clique nesse caso (só checava carrinho vazio / total negativo), então a venda "dava certo" e
escondia o problema.

**Corrigido reaproveitando o padrão já usado em `os/show.php` (modal Fechar OS)**, que já tinha
essa mesma armadilha resolvida: variável `pagValorManual` — a linha única de pagamento
acompanha o total automaticamente (`totais()` sincroniza `linhasPag[0].valor` sempre que
`linhasPag.length === 1 && !pagValorManual`) até o usuário editar o valor à mão, que é quando a
flag liga e o auto-sync para (pra não apagar um valor digitado de propósito, ex. ao dividir
pagamento). Na prática, agora o campo já vem preenchido com o total assim que a única linha
existe — selecionar a forma já basta, sem precisar digitar nada.

**Defesa extra no `btnFinalizar`**: mesmo com o auto-sync, se por algum motivo `pagamentosEnvio`
ainda sair vazio (ex.: usuário apagou o valor à mão e não repôs), o clique agora é bloqueado com
um alerta em vez de deixar a venda seguir pro fallback silencioso do backend.

## Bug: recibo do PDV não mostrava as parcelas do cartão

Reportado pelo usuário testando o fix acima: mesmo com a forma de pagamento certa no recibo
("Cartão de crédito"), uma venda simulada em 6x aparecia igual a uma venda à vista — nenhum "6x"
em lugar nenhum.

**Causa**: `PdvController::buscarVendaCompleta()` (usado pelo cupom térmico, pelo A4 e pelo PDF
do WhatsApp — os três reaproveitam o mesmo método) nunca buscava `pdv_venda_pagamentos`, só
`pdv_vendas` — e é só na primeira que o número de parcelas fica guardado por linha;
`pdv_vendas.forma_pagamento` vira `'misto'` quando há mais de uma forma, sem detalhe nenhum, e
pra forma única não carrega parcelas junto. Os dois recibos (`pdv/comprovante.php` cupom
térmico, `layouts/print_venda_pdv.php` A4) sempre mostraram só o nome da forma
(`$formasLabel[...]`), nunca a quantidade de parcelas — a informação simplesmente nunca tinha
sido buscada do banco pra chegar até a view.

**Corrigido**: `buscarVendaCompleta()` passou a trazer `pagamentos` (linhas de
`pdv_venda_pagamentos`). `$labelPagamento()` (função local, duplicada nos dois arquivos de
recibo — são views HTML independentes, sem um partial compartilhado entre elas) monta
`"Cartão de crédito (6x)"` quando `forma_pagamento='cartao_credito'` e `parcelas > 1`. Pagamento
dividido (mais de uma linha) ganhou tratamento à parte: badge "Misto" + uma lista com a forma e
o valor de cada linha, em vez de esconder o detalhe atrás de um badge genérico. Vendas antigas
sem linha em `pdv_venda_pagamentos` (nenhuma, já que a tabela sempre grava pelo menos 1 linha
desde que existe — mas defensivo contra dado que não bateu por algum motivo) caem de volta no
badge único a partir de `venda.forma_pagamento`, sem parcelas (não tem como saber quantas foram
sem a linha).

## Logo da empresa ocupando 100% da largura na sidebar

Pedido do usuário: a logo no topo da sidebar (`layouts/main.php`, bloco `.brand`) ficava pequena
com bastante espaço vazio ao lado, em vez de preencher a largura toda — reportado com print de
uma empresa cuja logo é mais quadrada/ícone do que uma faixa larga.

**Causa**: o `<img>` já tinha `width:100%`, mas também `max-height:48px` com
`object-fit:contain` — `contain` preserva a proporção da imagem original, então só preenche
100% da largura se a logo já for larga o bastante pra bater no teto de 48px de altura primeiro.
Pra uma logo quadrada/vertical, quem limita é a altura (48px), sobrando espaço vazio nas
laterais — exatamente o sintoma reportado. Confirmado com preview local (Playwright, sem
depender do banco): uma logo larga já preenchia bem antes; uma quadrada ficava pequena com
espaço vazio dos dois lados.

**Considerado e descartado**: trocar `object-fit:contain` por `cover` com altura fixa garante
100% da largura sempre, mas CORTA a logo quando ela não é larga o bastante (num teste com uma
logo quadrada com texto embaixo, o `cover` cortou o texto inteiro) — pior que o problema
original, já que corta conteúdo de marca da empresa.

**Corrigido**: `max-height:48px` virou `height:auto;max-height:200px` (mantendo
`object-fit:contain`, sem cortar nada) — a altura da logo passa a acompanhar a largura
disponível (~214px, largura da sidebar menos padding) até um teto generoso de 200px, suficiente
pra uma logo até perfeitamente quadrada preencher 100% da largura sem cortar. Efeito colateral
aceito conscientemente: empresas com logo mais quadrada/vertical passam a ter o bloco `.brand`
da sidebar mais alto que os 48px de antes — não quebra nada (`.sb-scroll`, o menu abaixo, já é
`flex:1 1 auto` com scroll próprio), só desloca o menu um pouco pra baixo. Logos largas (a
maioria dos casos reais) continuam exatamente como antes, já bem próximas de 48px de altura.

## Bug: texto claro demais em "Marcas Mais Atendidas" e "Melhores Clientes" (Relatórios)

Reportado pelo usuário com print + DevTools mostrando o nome da marca ("Samsung") renderizado
com `color: #666` inline, quase ilegível sobre o fundo branco do card. **Não achei nenhum
`#666` em `app/Views/relatorios/index.php`** neste checkout — os três `<div>` afetados
(`marca`/`total` em "Marcas Mais Atendidas", `nome` em "Melhores Clientes") não tinham `color`
nenhum no `style` inline, então herdavam a cor de algum ancestral; o `#666` que apareceu no
DevTools do usuário pode ser de uma versão do arquivo no VPS ligeiramente diferente desta (não
tenho acesso pra confirmar).

De qualquer forma, depender de herança pra essas labels é frágil — corrigido fixando
`color:#1e293b` (mesmo tom escuro já usado em `.chart-title` neste arquivo) explicitamente nos
três `<div>`: nome da marca, contagem "N OS" (marcas) e nome do cliente (clientes). Como
`.chart-card` tem fundo branco fixo (`background:#fff`, não muda com o tema do app), esse tom
escuro garante contraste em qualquer cenário, independente de qual `color` estava vazando antes.
Os valores secundários (receita da marca, "N OS" do cliente) já tinham `color:#64748b`
propositalmente — mais claros por serem informação secundária — esses não foram tocados.

## Status de OS: "Fechar automaticamente sem cobrança" (novo comportamento configurável)

Pedido do usuário: em Config → Status de OS, poder marcar um status (ex.: um "Descartado"
personalizado) pra que, assim que uma OS entrar nele, o sistema feche a OS sozinho como
"Sem Conserto/Recusado" — sem passar pelo modal "Fechar OS", usando os mesmos
documentos/impressões que esse fechamento já usa.

- **Migration `037_os_status_fecha_sem_cobranca.sql`** — `os_status.fecha_sem_cobranca`
  (TINYINT(1) DEFAULT 0), terceiro "comportamento" configurável ao lado de `permite_fechar` e
  `sem_valor`.
- **Só faz sentido pra status `tipo='cancelada'`** — é a mesma condição que já define
  "Sem Conserto/Recusado" em toda `OrdemServicoController` (`$ehSemConserto = tipo ===
  'cancelada'`, ver `fechar()`). O checkbox só aparece no formulário quando `tipo=Cancelada`
  está selecionado (`atualizarVisibilidadeFechaSemCobranca()` em `os_status/index.php`, ligada
  no `change` do select Tipo); o servidor (`OsStatusController::salvar()`) força `0` de novo se
  o tipo não bater, tanto pra status novo/personalizado quanto pra um nativo ajustado (ex.: o
  "Sem Conserto" nativo já seedado com `tipo=cancelada` — pra esse, o tipo não vem do POST,
  porque o campo fica travado no form, então usa o `tipo` já salvo no banco).
- **`OrdemServicoController::talvezFecharAutomaticoSemCobranca($osId, $eid, $statusAtualId)`**
  — chamado no fim de `atualizarStatus()` (troca rápida de status) e de `atualizar()` (form de
  edição da OS), os dois lugares que hoje mudam `status_id` de uma OS já existente. Se o status
  de destino tem `fecha_sem_cobranca=1`, roda o **mesmo bloco de campos** que o `fechar()`
  manual já grava pro caso `$ehSemConserto` (vai pro status "Fechado", `garantia_dias`/`_ate`
  nulos, `situacao_pagamento='pendente'`, `fechada_sem_receita=1`, preserva `valor_pago`
  existente — não zera um adiantamento genuíno recebido antes) — só que sem os campos que só
  existem no formulário do modal (laudo, desconto, solução aplicada), porque não há formulário
  nenhum sendo submetido aqui.
- **Ponto sutil pro histórico**: `nomeStatusSemConserto()` (usada por `imprimirSemConserto()` e
  `enviarPdfWhatsapp()` pra recuperar o nome do status "Sem Conserto"/"Descartado" depois que a
  OS já foi pro "Fechado") funciona lendo `os_historico` atrás de uma transição **saindo** de um
  status `tipo=cancelada` **entrando** no status atual — exatamente o formato que o fluxo manual
  (mudar status → depois abrir Fechar OS) sempre produziu. Por isso
  `talvezFecharAutomaticoSemCobranca()` só é chamado **depois** que o caller já atualizou
  `status_id` pro status flagado e já gravou esse histórico — ele registra uma SEGUNDA transição
  (do status flagado pro "Fechado"), reproduzindo o mesmo padrão de duas transições que o fluxo
  manual sempre teve, sem precisar tocar em `nomeStatusSemConserto()`/`imprimirSemConserto()` /
  nenhum dos documentos de impressão, que já funcionam sem mudança nenhuma.
- **Não mexe no Financeiro** — como não passa pelo bloco de lançamento de receita de `fechar()`
  (que já só roda quando `!$ehSemConserto`), nenhum lançamento é criado, igual ao fechamento
  manual "Sem Conserto".
- **Indicador na lista** de Config → Status de OS: badge vermelho "⚡ Fecha sozinho, sem
  cobrança" ao lado do já existente "✓ Fecha OS", pra deixar visível quais status têm esse
  comportamento sem precisar abrir cada um pra editar.
- **Deliberadamente sem confirmação/aviso na hora da troca de status** — o aviso fica só no
  formulário de configuração do status (texto em vermelho explicando o comportamento), a pedido
  implícito do "qualquer que seja o caminho, fecha direto" do pedido original; mover uma OS pra
  esse status por engano fecha ela de verdade, sem chance de cancelar depois (mesma
  irreversibilidade que fechar manualmente sempre teve).

## Fechamento "Sem Conserto/Recusado": equipamento devolvido ou descartado

Pedido do usuário testando o fechamento manual: o modal sempre assumia que o equipamento seria
devolvido ao cliente — não cobria o caso real de o cliente não querer/poder retirar (aparelho
sem valor, cliente sumiu, etc.), onde a assistência acaba descartando.

- **Migration `038_os_equipamento_descartado.sql`** — `ordens_servico.equipamento_descartado`
  (TINYINT(1) DEFAULT 0). Só é perguntado/gravado no fechamento Sem Conserto/Recusado
  (`OrdemServicoController::fechar()`, bloco `$ehSemConserto`) — em qualquer outro fechamento
  fica no default (0), o campo nem aparece no formulário.
- **UI**: dois rádios "Devolvido ao cliente" (padrão, já marcado) / "Cliente não vai retirar —
  descartado pela assistência" logo abaixo do aviso vermelho no modal Fechar OS (`os/show.php`),
  substituindo o texto fixo "o equipamento será devolvido ao cliente" que existia antes ali (a
  frase de fato virou uma pergunta, não mais uma afirmação).
- **`print_sem_conserto.php`** reflete a escolha: a frase final do aviso principal
  (`$fraseEquip`) e o parágrafo de observações no rodapé mudam de "disponível pra retirada..."
  pra "cliente optou por não retirar, equipamento fica sob responsabilidade da assistência pra
  descarte" quando `equipamento_descartado=1` — vale tanto pro caso "Recusado" quanto "Sem
  conserto" (a mesma escolha, reaproveitada nos dois textos). Nenhuma mudança precisou ser feita
  em `imprimirSemConserto()`/`enviarPdfWhatsapp()` — os dois já renderizam esse mesmo layout, o
  campo novo só influencia o texto dentro dele.
- **Fechamento automático (`talvezFecharAutomaticoSemCobranca()`, ver seção acima) não passa por
  este campo** — como não há modal nesse caminho, `equipamento_descartado` fica no default
  (0 = "devolvido"), igual a como o texto sempre assumiu antes desta mudança. Se isso importar
  no futuro, dá pra promover a decisão pra uma configuração por status (ao lado de "fecha sem
  cobrança"), mas não foi pedido agora.

## Serviços cadastrados: busca AJAX + seleção em lote

Pedido do usuário olhando a tela `/servicos` cheia de linhas `DEMO:` (do seed de dados
fictícios) — precisava de um jeito rápido de achar e de limpar várias de uma vez, em vez de
rolar a lista inteira e clicar no lixeiro linha por linha.

- **Busca**: campo no cabeçalho do card da lista, debounce de 250ms, reaproveita o mesmo
  endpoint `GET /api/servicos?q=` que já existia só pro autocomplete da OS (`buscarAjax()`,
  sem mudança nenhuma nele). Campo vazio restaura a tabela original (HTML cacheado em JS antes
  da primeira busca), sem precisar recarregar a página nem duplicar a query sem filtro.
- **Seleção**: um checkbox por linha (posicionado antes do botão de editar, a pedido) + um
  checkbox "selecionar todos" no cabeçalho da coluna Ações. Botão "Excluir selecionados (N)"
  aparece só quando há pelo menos 1 marcado.
- **`ServicosCatalogoController::excluirLote()`** (`POST /servicos/excluir-lote`) — mesmo soft
  delete (`ativo=0`) que `excluir()` já fazia, só que pra uma lista de ids de uma vez
  (`WHERE empresa_id=? AND id IN (...)`), com csrf via header `X-CSRF-Token` (mesmo padrão do
  `reordenar()` de `os_status/index.php`) já que é chamado por `fetch()`, não por um `<form>`.
  Rota registrada **antes** de `/servicos/{id}` no `routes/web.php` — o router casa por ordem de
  registro, então `excluir-lote` precisa vir primeiro pra não ser engolido pelo `{id}`.
- **Exclusão individual das linhas geradas pela busca** também passa por `excluirLote()` (com um
  array de 1 id) em vez de duplicar a rota `/servicos/{id}/excluir` — as linhas renderizadas
  pelo PHP continuam usando o `<form>` de sempre (não regride nada do que já funcionava); só as
  linhas que vêm da busca AJAX (sem `<form>` próprio, geradas via JS) usam o fetch.
- **Delegação de evento** pro botão de editar (`corpo.addEventListener('click', ...)` no
  `<tbody>`, em vez de `querySelectorAll('.btn-edit').forEach(...)`) — necessário porque a busca
  substitui o conteúdo do `<tbody>` inteiro; um binding fixo no carregamento da página só
  funcionaria nas linhas originais do PHP, não nas geradas depois pela busca.

## Serviço lançado na OS entra automaticamente no catálogo

Pedido do usuário: quando um serviço é adicionado numa OS (modal "Novo Serviço" em
`os/show.php`) — seja escolhido do autocomplete do catálogo, seja digitado avulso — a
descrição passa a existir em `servicos_catalogo` sozinha, sem precisar ir em `/servicos`
cadastrar de novo.

- **`OrdemServicoController::garantirServicoNoCatalogo($eid, $descricao, $valor)`** — chamado no
  fim de `adicionarServico()`, tanto no branch de criar quanto no de editar um `os_servicos`.
  Casa por descrição (`LOWER(descricao) = LOWER(?)`, mesmo critério de busca do catálogo) —
  se já existe e está ativo, não mexe em nada (não sobrescreve `valor_padrao`, que é só uma
  sugestão da empresa; um valor pontual cobrado numa OS específica não deve virar o novo
  padrão). Se existe mas foi excluído (`ativo=0`), reativa. Se não existe, cadastra novo com
  `valor_padrao` = o valor unitário que acabou de ser lançado na OS.
  - **Trunca pra 150 caracteres** antes de gravar — `servicos_catalogo.descricao` é
    `VARCHAR(150)` mas `os_servicos.descricao` é `VARCHAR(255)` sem limite no campo da OS;
    sem o truncamento um texto longo digitado avulso quebraria esse INSERT (erro de SQL) e
    derrubaria a ação principal (adicionar o serviço na OS) por causa de um efeito colateral.
- Só se aplica a `adicionarServico()` — é o único ponto do sistema que insere em `os_servicos`
  a partir de entrada do usuário; nenhum outro fluxo (ex.: Entrada de Garantia) duplica serviço
  de uma OS pra outra.

## Bug: chips de acessório quase invisíveis no modal "Entrada de Garantia"

Reportado pelo usuário com print: os chips de acessório disponíveis ("Base", "Cabo de força"
etc., passo 3 do modal "Entrada de Garantia" em `os/index.php`) apareciam com texto quase
ilegível — cinza claro sobre fundo cinza claro.

**Causa**: `gChip()` (JS, `os/index.php`) monta o chip "disponível" via `div.style.cssText`
fixando só `background:#f8f9fa`, sem fixar `color` nenhum — o texto ficava dependendo de
herança (cor de texto de algum ancestral), que nesse contexto resolvia pra uma cor clara,
quase sem contraste contra o fundo também claro. O chip "selecionado" (azul) já era seguro
porque fixava `color:#fff` explicitamente. Mesma categoria do bug de "Marcas Mais Atendidas"
em Relatórios (mais acima neste arquivo): depender de herança pra cor de texto é frágil.

**Corrigido**: `color:#212529` (texto escuro padrão do Bootstrap) fixado explicitamente no
chip "disponível", garantindo contraste contra o fundo `#f8f9fa` independente de onde ele é
renderizado. Verificado que o `.ag-chip` da Agenda (também teve resultado no grep por "chip")
é um componente diferente, já usa CSS custom properties com override explícito pro tema
escuro — não tinha o mesmo problema.

**Mesmo bug, segundo lugar**: reportado em seguida pelo usuário com outro print — a lista de
"OS elegíveis pra Garantia" (busca no passo 1 do mesmo modal, `buscarOsGarantia()` em
`os/index.php`) tinha o idêntico problema no hover: `onmouseenter`/`onmouseleave` inline
alternavam a classe `.bg-light` do Bootstrap (fundo claro) sem nunca fixar a cor do texto —
nome do cliente e "Concluída em .../valor" usavam só `fw-semibold`/`small`, sem `text-*`
nenhum, então herdavam a cor clara do tema e ficavam ilegíveis contra o fundo agora claro.
Trocado o toggle JS por CSS real (`.os-garantia-item:hover` + override de `color` nos
elementos sem cor própria, preservando `.text-success` pra não perder o verde do valor/badge
de garantia) — mais robusto que depender de JS pra cada instância da lista.

## Wizard de Nova OS: "Enter para continuar" em todos os passos

Pedido do usuário: o passo 0 (Cliente) do wizard de Nova OS (`os/form.php`) já tinha um botão
"Continuar" com tratamento especial — cinza/desabilitado até selecionar um cliente, azul/
clicável depois, com a dica "Enter para continuar" aparecendo junto. Os passos 1 (Equipamento)
e 2 (Defeito) usavam um "Próximo" comum, sempre clicável, sem esse reforço visual nem atalho
de teclado. Estendido o mesmo padrão pros três passos.

- **`habilitarContinuarStep(n)`** generalizada a partir da antiga `habilitarContinuarStep0()`
  (mantida como wrapper fino, pra não precisar tocar nos callers existentes) — usa
  `stepValido(n)` (já existente, mesma função que `irParaStep()` usa pra travar navegação)
  como fonte única de verdade pra decidir aviso/dica/estado do botão.
- **Passo 1 (Equipamento)**: `habilitarContinuarStep(1)` chamada dentro do handler de
  "Confirmar equipamento" — único ponto do sistema que preenche `fEquipTipo` (confirmado por
  grep; inclusive o preenchimento via IA do scanner só popula os campos do modal, quem clica
  em "Confirmar equipamento" continua sendo o usuário).
- **Passo 2 (Defeito)**: `habilitarContinuarStep(2)` no evento `input` do textarea
  `defeito_relatado`, ao lado do listener de `sincronizarResumoLateral()` que já existia ali.
- **Atalho de Enter**: um `keydown` global novo avança o passo atual (1 ou 2) se `stepValido()`
  passar — mas **pula quando o foco está numa `<textarea>`**, pra não interceptar a quebra de
  linha de quem está digitando o defeito/observações (diferente do passo 0, que faz Enter
  funcionar dentro de um `<input>` de busca de cliente, onde não há esse conflito).
- **Passo 3 (Prazo e valor) não entrou** — é o "Salvar OS" final, um `type="submit"` de verdade
  com vários campos e validação HTML5 nativa própria; gate-lo do mesmo jeito exigiria decidir
  quais campos contam como "step válido" ali, escopo maior que o pedido.

**Ajuste de contraste em seguida**: reportado pelo usuário com print — a própria dica "Enter
para continuar" (mesmo no passo 0, original, não só nos dois novos) estava com `font-size: 11px`
e `color: var(--text-4)`, o tom mais claro da escala de texto — pequena e quase ilegível. Mesma
categoria dos outros bugs de contraste já corrigidos neste arquivo. Ajustado pra `14px` e
`var(--text-2)` (mesmo tom já usado no aviso "Selecione um cliente para continuar" ao lado, pra
ficar consistente entre os dois textos desse mesmo componente).

## Telefone/WhatsApp exclusivo por cliente

Pedido do usuário: não deixar dois clientes da mesma empresa com o mesmo telefone/WhatsApp —
avisar no cadastro se o número já pertence a outro cliente.

- **`Cliente::porTelefoneDuplicado($telefone, $whatsapp, $ignorarId=null)`** — compara só os
  dígitos (`only_numbers()` no valor enviado + `REPLACE` encadeado na coluna, removendo
  `()`/`-`/espaço), então não depende da formatação exata que a máscara do IMask gravou.
  Verifica o número enviado contra `telefone` OU `whatsapp` de QUALQUER outro cliente da
  empresa (os dois campos contam como o mesmo "número de contato" pra esse fim, já que
  `espelharContato()` já trata os dois como intercambiáveis). `$ignorarId` exclui o próprio
  cliente ao editar. Não roda a query se os dois campos enviados vierem vazios (evita
  falso-positivo comparando string vazia contra string vazia de outros clientes sem telefone).
- **`ClienteController::erroTelefoneDuplicado()`** chamado nos três pontos que gravam cliente
  a partir de entrada do usuário — `salvar()` (form completo), `atualizar()` (edição) e
  `salvarAjax()` (`POST /api/clientes`, o modal "Cadastrar novo cliente" reaproveitado tanto no
  wizard de Nova OS quanto no PDV) — mesmo padrão de `erroDocumento()` (CPF/CNPJ) já existente
  ao lado. Form completo e edição usam flash+redirect; o AJAX devolve `{error: "..."}`, que o
  JS de ambos os modais (`os/form.php` e `pdv/index.php`) já sabia exibir genericamente (nenhuma
  mudança de JS precisou ser feita).
- **`OrdemServicoController::sincronizarRascunho()` (sincronização de OS criada offline) ficou
  de fora de propósito** — já tinha sua própria lógica de casar por telefone normalizado pra
  reaproveitar o cliente existente em vez de duplicar, só que com um comportamento diferente
  (silenciosamente usa o cliente já achado, em vez de bloquear com aviso) — correto pra esse
  fluxo automático em segundo plano, onde não há ninguém pra ler um aviso interativo.

## Diretório público: estratégia "isca grátis" + banner como custo do plano grátis

Pedido do usuário: mapear como o Diretório (`/assistencias`) funciona hoje, com a estratégia de
usá-lo como isca pra atrair novos usuários do sistema completo. Mapeamento revelou que boa parte
já era grátis (cadastro, reivindicação, galeria de fotos, avaliações, e até "destaque" no topo
das buscas via `EmpresaController::ativarDestaqueGratis()`) — mas achei um gate real,
`perfil_diretorio_completo()` (`app/Helpers/functions.php`, exige `licenca_ate >= hoje`, ou seja
plano PAGO do sistema completo, trial não conta), que trava edição de cidade/UF, lista de
Serviços, foto de capa e estatísticas de visitas pra quem só tem a conta-diretório
(`tipo_conta='diretorio'`). Também achei uma feature morta: os planos pagos de **banner**
(`diretorio_planos` tipo `banner`, `DiretorioAnunciosController`) tinham toda a engrenagem de
compra/aprovação pronta, mas `diretorio_banners` nunca era lido em nenhuma página pública —
empresa podia pagar por um banner aprovado que nunca aparecia pra ninguém.

**Desenho acordado com o usuário**: reaproveitar esse banner morto como o "custo" do plano
grátis — em vez de eliminar a feature, plugá-la exatamente nos perfis que não pagam nada:

- **`DiretorioController::empresa()`** — quando o perfil está **reivindicado** E
  `!perfil_diretorio_completo($empresa)` (mesmo critério do gate de edição), busca 1 banner
  aleatório entre os aprovados com assinatura `status='ativo'` e não vencida
  (`diretorio_banners` JOIN `diretorio_assinaturas`). Perfil com plano pago ativo nunca mostra
  anúncio — é mais um benefício de assinar, junto com edição completa do perfil.
- **View** (`diretorio/empresa.php`) — bloco "Publicidade" no sidebar de contato, abaixo do
  selo "Empresa verificada pelo FixaOS", só quando `$anuncio` existe.
- **Aviso pro dono do perfil** (`empresa/perfil_publico.php`) — card informativo (só quando
  `!$planoCompleto && reivindicada`) explicando que o perfil grátis pode exibir anúncio de
  outra empresa, com link "Ver planos" — transparência pedida explicitamente ("essa informação
  aparece para quem quer o diretório gratuito").
- **`AuthMiddleware::handle()`** — a lista `$liberado` de contas `soDiretorio()` (só tinham
  acesso a perfil público + fórum) ganhou `/planos`, `/assinar`, `/pagamento`: sem isso, o link
  "Ver planos" novo levaria a conta-diretório de volta pro próprio perfil (rota bloqueada),
  quebrando exatamente o CTA que devia converter esse lead.

**Achados mapeados mas NÃO mexidos nesta rodada** (ficam registrados pra decisão futura):
- Plano pago "destaque" (`diretorio_planos` tipo `destaque`, moderado manualmente pelo master)
  ficou redundante desde que `ativarDestaqueGratis()` passou a oferecer a mesma coisa de graça
  — hoje convivem os dois caminhos pro mesmo resultado.
- Tier "premium" de destaque (badge ⭐ diferente do 🔥 básico) não tem nenhuma vantagem
  funcional sobre o básico — a ordenação de busca (`encontrar()`) trata os dois igual.
- Não existia nenhum CTA proativo (banner, e-mail) convidando conta-diretório a testar o sistema
  completo — hoje só existe a mensagem passiva do `AuthMiddleware` quando a conta tenta acessar
  uma rota bloqueada. Endereçado em parte logo em seguida (ver próxima seção).

**CTA proativo pra ver a página pública** (pedido do usuário em seguida, vendo a tela): card
azul chamativo no topo de `empresa/perfil_publico.php`, acima do card de destaque — "Veja sua
empresa na internet / Alcance mais clientes, de graça", com a URL pública em destaque (`<code>`,
copiável visualmente) e um botão grande "Ver minha página" abrindo em nova aba. Substituiu o
botão pequeno `btn-outline-primary btn-sm` que ficava discreto no canto superior direito
(mesmo link, `$urlPublica`, só que quase imperceptível antes). Só aparece quando a empresa já
tem `slug` (perfil publicado).

**Bug de contraste no card "Desbloqueie a edição completa do perfil"**: reportado pelo usuário
com print — mesma categoria dos outros bugs de contraste já documentados neste arquivo. O card
(teaser exibido no lugar dos campos travados quando `!$planoCompleto`) tem fundo claro fixo
(`linear-gradient(135deg,#fff7ed,#fff)`), mas o `<h6>`/`<p>`/badges não fixavam cor de texto —
ficavam quase invisíveis no tema escuro. Corrigido com tons escuros de âmbar (`#78350f`/
`#9a3412`) fixados explicitamente, combinando com a paleta laranja/dourada do card.

## Seção de Avaliações liga/desliga por empresa

Pedido do usuário: dar controle pra empresa esconder a seção de Avaliações (resumo, lista de
comentários e formulário "Deixe sua avaliação") da própria página pública, se ela não quiser
exibir isso.

- **Migration `039_empresas_avaliacoes_publicas.sql`** — `empresas.avaliacoes_publicas`
  (TINYINT(1) DEFAULT 1) — default ligado, pra não mudar o comportamento de perfil já publicado.
- **Toggle** em Empresa → Perfil Público (`empresa/perfil_publico.php`), mesmo padrão visual do
  toggle "Aparecer no diretório público" já existente ao lado — **não é gate de plano**, salva
  independente de `planoCompleto` (`EmpresaController::salvarPerfilPublico()`, update separado
  dos dois branches de plano/sem-plano, já que se aplica aos dois).
- **`DiretorioController::empresa()`** zera `$avaliacoes`/`$estatisticas` quando desligado (a
  view usa isso pra calcular nota média e contagem) e passa `$avaliacoesAtivas` — a view
  (`diretorio/empresa.php`) envolve o bloco inteiro (resumo + lista + formulário) nesse `if`.
  Desligar **não apaga** avaliações já recebidas, só esconde a seção enquanto ficar assim.
- **`DiretorioController::avaliar()`** (endpoint que recebe um novo envio) também checa
  `avaliacoes_publicas` no servidor antes de aceitar — defesa em dupla camada, pra um POST
  direto não conseguir enviar avaliação pra empresa que desligou a seção.
- Gerenciamento interno (responder/contestar avaliação já recebida, no próprio painel da
  empresa) não foi afetado — continua disponível mesmo com a seção pública desligada.

**Reposicionado a pedido do usuário**: o toggle "Exibir avaliações de clientes" saiu de perto de
"Visibilidade" (lá embaixo) e passou pra logo abaixo da seção Logo/Identificação, como uma faixa
de largura total antes do bloco "Cidade/UF, capa e redes sociais" (que só aparece com plano
completo) — fica visível bem no topo da página pra qualquer empresa, com ou sem plano.

**Bug de contraste sistêmico, achado no caminho**: a dica `.form-text` do Bootstrap (ex.: "JPG,
PNG ou WebP até 2MB", abaixo do campo de logo) nunca tinha cor própria no tema escuro — mesma
categoria dos outros bugs de contraste já corrigidos, só que esse é sistêmico (afeta toda dica
`.form-text` do sistema inteiro, não um componente isolado). Corrigido de uma vez em
`public/css/tokens.css` (`[data-theme="dark"] .form-text { color: var(--text-3) !important; }`),
mesmo padrão já usado pra `.text-muted`/`.form-label` logo acima na mesma folha de estilo.

**Reposicionado de novo a pedido do usuário**: saiu de faixa própria de largura total e virou
mais um campo dentro do card "Identificação da empresa", logo abaixo de "WhatsApp público" —
label + switch na mesma linha, dica embaixo, mesmo padrão visual dos outros campos do card
(nome, descrição, horário, WhatsApp).

## Diretório: cidade, foto de capa, redes sociais e serviços viram grátis

Pedido do usuário, continuando a estratégia de "isca grátis": tirar do card "Desbloqueie a
edição completa do perfil" tudo que dava pra liberar sem custo, deixando só **contagem de
visitas** como benefício exclusivo de quem assina o sistema completo — e trocar o card por um
convite de verdade pro sistema completo (OS, financeiro etc.), com link pra demonstração ao
vivo.

- **`EmpresaController::perfilPublico()`** — Serviços (`empresa_servicos`) passa a carregar
  sempre, fora do `if($planoCompleto)`. Só o bloco de estatísticas de visitas (`diretorio_visitas`)
  continua condicionado.
- **`EmpresaController::salvarPerfilPublico()`** — as duas branches de UPDATE (`if/else` por
  `$planoCompleto`) viraram uma só, sempre grava cidade/UF/site/redes sociais/especialidades;
  o delete+reinsert de `empresa_servicos` e o upload de `foto_capa` (antes só dentro do
  `if($planoCompleto)`) também passaram a rodar sempre. `$cidade`/`$uf` não dependem mais de
  `$planoCompleto` pra vir do POST.
- **View (`empresa/perfil_publico.php`)** — o `if($planoCompleto)` que envolvia Foto de capa +
  Cidade/redes sociais + Serviços + Visitas (tudo num único bloco) ficou só ao redor de
  "Visitas ao perfil" agora; o resto ficou incondicional. Removido o badge "Plano completo" do
  header do card de cidade/redes sociais (não faz mais sentido).
- **Card reescrito** (era "Desbloqueie a edição completa do perfil") — agora "Conheça o FixaOS
  completo", com badges de Ordens de Serviço/Financeiro/Estoque/Contagem de visitas, botão
  "Ver demonstração ao vivo" (`/demo`) ao lado de "Ver planos da FixaOS".
- **`GuestMiddleware`** — `/demo` é uma rota só-pra-visitante (redireciona quem já está logado
  pro `/dashboard`), o que quebraria o botão novo pra uma conta só-diretório (ela já está
  logada). Abriu uma exceção pontual: `soDiretorio()` acessando especificamente `/demo` passa
  direto — `AuthController::demo()` troca a sessão pra conta demo (`Auth::login()` já recarrega
  `tipo_conta` do zero, então a sessão deixa de ser `soDiretorio()` depois disso). Nenhuma outra
  rota de visitante (`/login`, `/cadastrar` etc.) foi liberada, só essa.
- **Textos desatualizados corrigidos**: o aviso "Seu perfil é grátis..." (`perfil_publico.php`)
  e a mensagem do modal "Reivindicar perfil" (`diretorio/empresa.php`) ainda citavam cidade/
  serviços/foto de capa como benefício pago — ajustados pra citar só a contagem de visitas (e,
  no modal de reivindicar, o convite pro sistema completo).

## Upload de logo: recorte/redimensionamento livre + PNG transparente

Pedido do usuário: tornar o upload de logo (Empresa → Perfil Público) mais dinâmico — recortar,
redimensionar e editar livremente antes de salvar, sempre em PNG com fundo transparente.

- **Client-side (`empresa/perfil_publico.php`)** — reaproveita o Cropper.js já usado no Editor
  de Imagens (`editor_imagens/index.php`), mas embutido direto no upload em vez de mandar pra
  outra tela: escolher o arquivo abre `#modalEditorLogo` com recorte livre/quadrado/2:1 +
  campos de largura/altura sincronizados com o cropper (mesmo padrão de UI do editor completo).
  Ao confirmar, `_logoCropper.getCroppedCanvas({fillColor:'transparent'})` preserva o alfa fora
  da área recortada — sem isso, a área recortada apareceria preenchida de branco/preto, sem
  transparência nenhuma — e o resultado vira um `File` PNG injetado direto no
  `<input type="file" name="logo">` via `DataTransfer`, então o formulário continua enviando
  multipart normalmente, sem precisar de endpoint novo. SVG pula o cropper (é vetor, recortar
  em pixels não faz sentido) e segue pro preview simples de sempre.
- **Server-side (`EmpresaController::processarLogo()`)** — reescrito pra sempre salvar como PNG
  com canal alfa habilitado (`imagealphablending(false)` + `imagesavealpha(true)`), substituindo
  o pipeline antigo que redimensionava e depois convertia pra WebP. Isso vale tanto pra quem
  passa pelo editor (o arquivo já chega como PNG) quanto pra quem envia direto sem JS — o
  redimensionamento de fallback (`redimensionarComTransparencia()`, teto 400×200) também
  preenche a "tela" nova com um pixel transparente antes de copiar a imagem, em vez de deixar
  a cor de fundo padrão do GD (preto). SVG continua salvo como está (vetor, transparência
  nativa). Como `processarLogo()` é compartilhado com o upload de logo em Configurações →
  Empresa (`empresa/index.php`), esse formato novo vale pros dois lugares — só o editor visual
  de recorte ficou restrito à tela de Perfil Público, que foi onde o pedido apontou.

## Bug: busca do "Atendimento rápido" (Agenda) não retornava nada numa empresa

Reportado pelo usuário (empresa "Timetec"): digitar no campo "OS, cliente ou aparelho" do modal
Atendimento Rápido não mostrava nenhum resultado — nem a busca em si, nem a caixa "Nada
encontrado". Não reproduzi localmente, mas o usuário mandou o print do Console (F12) com a causa
exata: `Uncaught ReferenceError: bootstrap is not defined`, disparado de dentro de um
`forEach` — não era o que eu tinha suspeitado numa primeira tentativa (fragilidade genérica em
`agendaCriarBusca()`, ver commit anterior — mantida como reforço, mas não era a causa real).

**Causa real**: `agenda/index.php` tem duas chamadas de `bootstrap.Popover.getOrCreateInstance()`
soltas no topo do script (fora de função, sem guard) — uma no carregamento inicial da página
("Popover '+N mais'") e outra dentro do `.then()` que reconcilia a grade depois de uma ação
(arrastar, mudar status). Se o bundle do Bootstrap (carregado via CDN) falhar — rede instável,
CDN fora do ar, extensão de navegador bloqueando — no exato momento em que uma dessas linhas
roda, `bootstrap` fica `undefined` e a chamada lança uma exceção síncrona não tratada. Como as
duas ficam **antes**, no mesmo bloco `<script>`, de onde `agendaCriarBusca()` registra as três
buscas da página (Cliente, OS do modal completo, OS do Atendimento Rápido), a exceção interrompe
o script ali mesmo e nenhuma busca chega a ser registrada — Atendimento Rápido é só a mais
visível porque é a última.

**Corrigido**: as duas chamadas agora ficam atrás de `if (typeof bootstrap !== 'undefined')` —
se o Bootstrap não carregou, só o popover "+N mais" fica sem funcionar (degradação aceitável),
em vez de travar o resto do script da página inteira.

## Bug: link de acompanhamento de OS mostrava o pitch de venda do FixaOS no preview do WhatsApp

Reportado pelo usuário com print: ao compartilhar o link "Acompanhar OS" pelo WhatsApp, o card de
preview mostrava "FixaOS — Gestão para Assistências Técnicas" / "Sistema completo de gestão...
Teste grátis 7 dias, sem cartão" — o pitch de venda do sistema pro DONO da assistência, exibido
justamente pro CLIENTE final que só quer ver o andamento do reparo dele. Confuso e fora de lugar.

**Causa**: `OrdemServicoController::acompanhar()` renderiza `os.acompanhar` com o layout
`landing.php` (o mesmo do site institucional) mas nunca passava `$tituloFull`/`$metaDesc`/
`$ogImage` — sem isso, o layout cai nos valores padrão dele, que são literalmente o texto de
venda da landing page (mesmo padrão de bug já corrigido antes na auditoria de SEO do Diretório).

**Corrigido**: `acompanhar()` agora monta título/descrição/imagem específicos da página —
`"Acompanhamento da OS {numero} — {empresa}"`, `"Status atual: {status}. Acompanhe o reparo do
seu {equipamento} na {empresa}."`, e usa a logo da própria empresa como `og:image` quando
existir (em vez do ícone genérico do FixaOS). Nada de "teste grátis"/"sem cartão" — a página é
só sobre o reparo do cliente, sem misturar com a venda do sistema pro dono da assistência.

**Ajuste em seguida — logo distorcida no preview mobile**: reportado pelo usuário com print — a
logo aparecia esticada/distorcida no card de preview do WhatsApp. Causa: `og:image` sem
`og:image:width`/`og:image:height` — o crawler (WhatsApp/Facebook) assume uma proporção padrão
larga pro card e estica a imagem real pra caber nela, distorcendo qualquer logo que não seja
nessa proporção (a maioria não é, já que o editor de logo permite recorte livre). Corrigido:
`landing.php` ganhou suporte a `$ogImageWidth`/`$ogImageHeight` (emite as duas tags só quando
presentes); `acompanhar()` lê as dimensões reais do arquivo via `getimagesize()` antes de usar a
logo como `og:image` — se não der pra ler (arquivo ausente, corrompido) ou for SVG (crawlers de
preview não renderizam SVG em `og:image`), simplesmente não usa a logo, caindo no ícone genérico
em vez de arriscar mostrar algo distorcido. Mesmo ajuste aplicado em `DiretorioController::
empresa()` (a foto de capa do perfil público tinha o mesmo risco, menor mas real).

**Segundo ajuste — logo removida do link preview (`os/acompanhar` apenas)**: mesmo com a
proporção correta (ajuste acima), o usuário testou de novo e o card do WhatsApp mostrava a logo
bem "zoomada"/cortada rente ao texto — proporção correta, mas o tamanho do card em si (grande,
ocupando a largura toda) é decidido pelo próprio WhatsApp, sem controle possível via `og:image`;
o efeito de corte vinha da proporção alongada da logo em si (faixas de cor + marca no meio)
combinada com esse card grande. Pedido do usuário: tirar a logo do link, mantendo-a só dentro da
própria página de acompanhamento. `OrdemServicoController::acompanhar()` não monta mais
`$ogImage`/`$ogImageWidth`/`$ogImageHeight` — sem a variável, `landing.php` cai no fallback já
existente (ícone genérico do FixaOS). A logo da empresa continua aparecendo normalmente dentro
da página `os/acompanhar` em si (variável separada, `$os['empresa_logo']`, não afetada por essa
mudança) — só o card de preview do link deixou de usá-la.

## Bug: garantia podia contar a partir de "Concluída", não do fechamento real da OS

Reportado pelo usuário com print do card "Garantia do serviço" — pedido: "a garantia só pode
contar a partir do momento que a OS for fechada".

**Causa**: dois pontos calculavam `garantia_ate` a partir de `data_conclusao`, achando (por um
comentário desatualizado no código) que essa era a data do fechamento. Não é — `data_conclusao`
é gravada assim que o status vira `tipo='concluida'` (reparo pronto, ainda aguardando retirada/
pagamento), um passo ANTES do fechamento de verdade (`tipo='entregue'`, que só acontece via o
modal "Fechar OS"/`fechar()`). `fechar()` em si sempre calculou `garantia_ate` certo (a partir de
"hoje", no momento do fechamento) — o bug estava em dois caminhos que recalculam depois:
- **`atualizarGarantia()`** (`POST /os/{id}/garantia-dias`, o campo "Garantia: N dias" editável
  direto no cabeçalho da tela da OS, sempre visível mesmo com a OS ainda não fechada) — se a OS
  já tinha `data_conclusao` (só "Concluída", não "Entregue") e o usuário mexesse nesse campo, a
  validade recalculada usava essa data cedo demais, adiantando o vencimento da garantia.
- **`abrirGarantia()`** (Entrada de Garantia) — mesmo fallback, usado quando uma OS antiga não
  tinha `garantia_ate` gravado; caía pra `data_conclusao` em vez da data de entrega real.

**Corrigido**: os dois passaram a usar `data_entrega` (só gravada quando o status vira
`entregue` de verdade — `fechar()` sempre grava, `atualizar()`/`atualizarStatus()` também, nos
poucos casos de mudança de status fora do modal) como base do cálculo, em vez de
`data_conclusao`. `atualizarGarantia()` também só recalcula `garantia_ate` quando `data_entrega`
existe — antes disso, editar os dias de garantia numa OS ainda aberta ou só "Concluída" agora
só atualiza `garantia_dias` (o prazo em si, que `fechar()` vai usar quando a OS for fechada de
verdade), sem gravar uma validade prematura.

**Não corrige dados já salvos** — sem acesso ao banco de produção, uma OS que já teve
`garantia_ate` calculada errado por esses dois caminhos (contando desde "Concluída") continua
com a data antiga até alguém editar os dias de garantia de novo (agora recalculando certo, a
partir de `data_entrega`) ou reabrir/fechar a OS de novo.

**Ajuste em seguida — editar não corrigia OS ainda não entregue**: reportado pelo usuário com
print (mesmo card, mesma data errada) depois de já ter editado o campo "Garantia: N dias" com o
primeiro fix no ar. Causa: o guard `!empty($os['data_entrega'])` era rígido demais — numa OS que
NUNCA chegou a ser entregue de verdade (só passou por "Concluída", que foi o que deixou o
`garantia_ate` errado gravado antes deste fix), `data_entrega` está vazio, então o endpoint
simplesmente não tocava em `garantia_ate` nenhuma — o valor antigo errado ficava lá pra sempre,
mesmo editando os dias repetidamente. `atualizarGarantia()` agora consulta o `tipo` do status
atual da OS: se for `entregue` de verdade, recalcula a partir de `data_entrega` (com fallback pra
`data_conclusao` só em OS entregue importada sem essa coluna preenchida); se NÃO for `entregue`
(inclusive "Concluída"), **limpa** `garantia_ate` pra `null` — a OS ainda não fechou, então não
deve ter validade de garantia nenhuma até isso acontecer. Isso também limpa sozinho qualquer
resíduo do bug antigo assim que alguém mexe no campo, sem precisar de acesso ao banco. Mesmo
fallback pra `data_conclusao` (só quando `status_tipo === 'entregue'`) aplicado em
`abrirGarantia()`, que antes também podia deixar de recalcular silenciosamente pro mesmo tipo de
OS entregue antiga sem `data_entrega`.

**Fix definitivo — validação passou a ser no momento de EXIBIR, não só ao editar**: reportado
pelo usuário de novo (mesmo card, mesma data), agora com print mostrando o número da OS (0498).
Os dois ajustes anteriores só corrigiam o dado quando alguém *mexia* no campo "Garantia: N dias"
— uma OS com o resíduo do bug original, que ninguém foi editar, continuava mostrando a validade
errada pra sempre, e o usuário não tem como saber quais OS estão afetadas sem abrir uma por uma.
Fix de verdade: `OrdemServico::findCompleto()` (usada por `os/show.php`) agora zera
`$os['garantia_ate']` **no array retornado** (nunca grava isso na tabela) sempre que
`status_tipo !== 'entregue'` — a tela deixa de confiar cegamente no que está gravado no banco;
qualquer OS que ainda não fechou de verdade nunca mostra o card de garantia, resíduo de bug
antigo ou não, sem precisar editar nada. Mesmo raciocínio aplicado em `buscarEmGarantia()`
(`GET /api/os/garantia?q=`, a busca do passo 1 do modal "Entrada de Garantia") — antes aceitava
qualquer status `NOT IN ('cancelada')` (incluindo OS ainda aberta/em andamento/concluída) e caía
pra `data_conclusao` quando `garantia_ate` não estava gravado; agora exige `s.tipo = 'entregue'`
e usa `COALESCE(os.data_entrega, os.data_conclusao)` como base do fallback.

## Divulgação do Diretório: convite pra publicar + páginas por cidade

Pedido do usuário: ideias de como usar o Diretório grátis (ver seção acima, "Diretório público:
estratégia 'isca grátis'...") pra trazer tráfego de verdade, não só ficar disponível esperando
alguém achar. Duas frentes implementadas juntas, priorizadas por esforço/retorno:

**1. Convite proativo pra quem ainda não publicou** — `NotificacaoService::verificarDiretorioNaoPublicado()`,
mais um `verificarX()` no mesmo padrão de `gerarTodas()` (já rodava a cada 5 min por empresa via
`NotificacaoController::gerarThrottled()`, sem precisar de infraestrutura nova). Condição: empresa
`tipo_conta='completo'` (conta-diretório já É só o diretório, não faz sentido convidar), ativa, com
3+ dias de conta (dá tempo de configurar o básico antes de cutucar) e sem perfil publicado (`slug`
vazio OU `listagem_publica=0`). Diferente dos outros `verificarX()` (alertas operacionais, que
fazem sentido repetir a cada 6h via o de-dup padrão de `criar()`), este é convite de crescimento —
teria efeito contrário repetindo toda hora, então usa uma janela própria de 30 dias antes de
notificar de novo. Link vai direto pra `/empresa/perfil-publico`.

**2. Páginas por cidade indexáveis** (`/assistencias/{uf}/{cidade-slug}`) — hoje só `/assistencias`
puro e cada perfil individual são indexáveis; qualquer busca filtrada (`encontrar()`) leva
`noindex` de propósito, então nenhuma URL do diretório captura buscas locais tipo "assistência
técnica em Campinas" no Google. `DiretorioController::encontrar()` foi refatorado: a lógica de
query/filtro/relaxamento virou `buscarListagem(array $q): array` (método privado, antes inline no
próprio `encontrar()`), reaproveitada agora por `DiretorioController::cidade(string $uf, string
$cidadeSlug)`.
- **Resolução do slug**: `cidade` é campo de texto livre (sem coluna própria de slug) — `cidade()`
  busca todas as cidades distintas do UF entre empresas públicas e compara `slugify()` de cada uma
  contra o slug da URL, até achar a que bate. Mesma função (`slugify()`, helper global) usada tanto
  pra montar o link quanto pra resolver de volta, então a ida e volta é sempre consistente.
- **Gate de conteúdo raso**: `DiretorioController::MIN_EMPRESAS_PAGINA_CIDADE` (3, público —
  `SitemapController` usa a mesma constante) — cidade com poucas empresas não gera página própria
  (302 pra busca geral já filtrada) pra não virar "conteúdo fino" que o Google penaliza.
  `SitemapController::xml()` só lista cidade que já passa desse mínimo.
  - **Não fecha 100% os casos de variação de grafia** (mesma cidade digitada diferente por
    empresas diferentes, ex. "Sao Paulo" sem acento vs "São Paulo") — o filtro de listagem usa
    `LIKE`, que sob a collation padrão (`utf8mb4_*_ci`) já é acento-insensível, então cobre a
    maioria dos casos reais sem esforço extra; normalizar `cidade` de verdade (índice próprio,
    dedup) é trabalho de dado maior, fora de escopo aqui.
- **SEO só na página "limpa" da cidade**: filtro extra (busca, serviço, bairro, raio/geo, nota
  mínima) ou paginação além da 1ª continuam levando `noindex,follow` — mesmo critério de
  `encontrar()`, só que a URL sem filtro extra passa a ser indexável com título/descrição/canonical
  próprios ("Assistência Técnica em Campinas, SP — N empresas avaliadas | FixaOS").
- **H1 dinâmico**: `diretorio/encontrar.php` ganhou um `if (!empty($cidadePagina))` no H1 do hero
  — só a rota de cidade passa essa variável, então a busca geral continua com o H1 genérico de
  sempre ("Encontre a assistência técnica mais perto de você").
- **Linkagem interna**: cada perfil de empresa (`diretorio/empresa.php`) ganhou um link "Ver todas
  em {cidade}/{uf} →" ao lado do heading "Outras assistências em {cidade}" (que já existia,
  listando 4 similares) — sem isso as páginas de cidade não teriam nenhum link apontando pra elas
  de dentro do site, só pelo sitemap.
- **`routes/web.php`**: `/assistencias/{uf}/{cidade}` registrada antes de `/assistencias/{slug}` —
  não colidem de verdade (o router casa por contagem de segmentos do path), mas mantém a convenção
  já documentada aqui ("rotas específicas antes das com parâmetro").

## Disparo de e-mail de prospecção (convite pro diretório grátis)

Pedido do usuário, continuando a divulgação do Diretório: convidar por e-mail as empresas já
importadas em `/master/prospeccao` (dados abertos de CNPJ da RFB, ver seção acima "Divulgação do
Diretório") que têm e-mail público cadastrado — ex.: buscar todas as empresas de "Feira de
Santana, BA" e mandar um convite pro diretório grátis pra quem tem e-mail. Pergunta do usuário
sobre volume seguro (20 e-mails/dia) respondida e incorporada como o padrão do sistema:

- **Por que 20/dia é seguro, e como subir sem risco**: não existe um número "oficial" universal
  de limite anti-spam — o que importa é reputação de domínio/IP e as duas métricas que todo
  provedor de e-mail observa: **taxa de bounce** (e-mail inexistente/caixa cheia) e **reclamação
  de spam**. 20/dia é bem conservador pra qualquer domínio, mesmo sem histórico de envio — dá pra
  ir dobrando a cada poucos dias (20 → 40 → 80…) enquanto essas duas métricas continuarem baixas.
  O risco real de subir rápido demais não é "ser bloqueado uma vez" — é a reputação do domínio
  cair a ponto de os e-mails **de verdade** do sistema (confirmação de cadastro, recibos) também
  começarem a cair em spam, já que usam o mesmo SMTP (`config/email.php`). `config/prospeccao_email.php`
  (`limite_diario`, padrão 20) é o único lugar que precisa mudar pra ajustar isso — comentário no
  arquivo já traz esse racional.
- **Migration `040_leads_prospeccao_email.sql`** — `leads_prospeccao` ganhou
  `email_convite_enviado_em` (marca quando o convite foi mandado — nunca reenvia pro mesmo lead,
  é um convite único, não uma campanha recorrente) e `email_unsub_token` (token aleatório por
  envio, usado só no link de descadastro daquele e-mail específico).
- **`EmailService::convitePropeccao()`** — mesmo padrão visual dos outros templates do serviço
  (`boasVindas()`, `perfilReivindicado()`), convite curto com CTA "Cadastrar grátis" apontando pra
  `/diretorio/cadastrar` (o mesmo formulário de auto-cadastro que já existia) e link de descadastro
  no rodapé (boa prática/LGPD pra e-mail frio, mesmo sendo dado público de CNPJ, não pessoa física).
- **`MasterController::prospeccaoDisparar()`** (`POST /master/prospeccao/disparar`) — dispara pros
  leads do **filtro atual da tela** (agora com filtro de cidade também, `municipio LIKE`) que têm
  e-mail e nunca receberam convite (`email_convite_enviado_em IS NULL`), respeitando o que resta do
  limite diário (`limite_diario` menos o que já saiu hoje, `email_convite_enviado_em >= CURDATE()`).
  Só marca como enviado (consome o lead da fila de elegíveis) se `EmailService::send()` retornar
  `true` — uma falha de conexão SMTP não pode fazer o lead "sumir" sem ninguém perceber, ele
  continua elegível pro próximo disparo.
- **`MasterController::prospeccaoDescadastrar($token)`** (`GET /prospeccao/descadastrar/{token}`,
  rota pública de propósito, sem `MasterMiddleware`) — marca o lead como `status='descartado'`
  (mesmo enum já usado pro descarte manual), então some da lista de "novos" sem precisar de nova
  coluna nem lógica separada pra "descadastrado".
- **Inicialmente manual, depois automatizado por pedido do usuário** — a primeira versão era só o
  botão "Disparar agora" (clique explícito do master a cada rodada). O usuário pediu disparo
  diário automático "pra estados diferentes" — ver "Disparo diário automático" logo abaixo.

## Bug: nova OS "duplicava" o equipamento da OS anterior

Cliente reportou: criou uma OS, foi criar outra com equipamento diferente, mas a segunda saiu
com o mesmo equipamento da primeira. Não reproduzi via banco (não tenho acesso), mas a causa mais
consistente com o sintoma (aleatório, "esqueci de trocar o equipamento sem perceber que ainda
estava lá") é **bfcache** (back-forward cache do navegador): depois de criar a OS #1 e ser
redirecionado pra `/os/{id}`, se o usuário aperta o botão **Voltar** do navegador em vez de clicar
em "+ Nova OS" de novo, o navegador restaura a página do formulário exatamente como estava ANTES
do submit anterior — sem rodar a inicialização de novo. Os campos ocultos de equipamento
(`equip_tipo`/`equip_marca`/`equip_modelo`, preenchidos pelo passo "Confirmar equipamento")
continuam com o valor da OS #1. Se o usuário não reabre o modal de equipamento (a tela parece em
branco à primeira vista — cliente, defeito etc. já limpos visualmente em alguns navegadores), a
OS #2 salva com o mesmo equipamento da anterior. Não é duplicação no banco (cada OS grava sua
própria linha em `equipamentos`) — é o MESMO dado sendo reenviado sem o usuário perceber.

**Corrigido**: `os/form.php` (só na tela de "Nova OS", não no editar) ganhou um listener de
`pageshow` — se `event.persisted` for `true` (página veio do bfcache, não de um carregamento
normal), força `location.reload()`, garantindo que o formulário sempre comece limpo de verdade
quando restaurado dessa forma. Um F5 manual do usuário já resolvia isso, mas ninguém sabia que
precisava fazer isso.

## Fix: filtrar_rfb_estabelecimentos.php estourava memória com dados reais

Rodado pela primeira vez contra o dataset real da Receita (dados abertos de CNPJ, ver
"Divulgação do Diretório" acima) — deu `PHP Fatal error: Allowed memory size ... exhausted`
logo no início, mesmo com `--limite=1000`.

**Achados no caminho, sobre o dataset real**:
- A Receita migrou o repositório de arquivos pra uma plataforma "SERPRO+" — o link antigo (`arquivos.receitafederal.gov.br/dados/cnpj/...`) não existe mais. Caminho atual:
  gov.br/receitafederal → Acesso à Informação → Dados Abertos → Cadastros → "Cadastro Nacional
  da Pessoa Jurídica (CNPJ)" → recurso "Inscrições no CNPJ" → pasta do mês mais recente.
- Os arquivos dentro de cada `.zip` **não têm extensão `.csv` nem o nome
  `Estabelecimentos0`/`Empresas0`/`Municipios`** que o script espera — vêm como
  `K3241.K03200Y0.D60808.ESTABELE`, `K3241.K03200Y0.D60808.EMPRECSV`,
  `F.K03200$Z.D60808.MUNICCSV` etc. Precisa renomear manualmente antes de rodar o script
  (o comentário no topo do arquivo agora documenta isso).

**Causa do estouro de memória**: o script carregava `Empresas0.csv` (2,2 GB de texto, sozinho)
inteiro num array associativo do PHP antes de filtrar qualquer coisa — um array desse tamanho em
PHP consome bem mais que o texto bruto (overhead por entrada), estourando o `memory_limit`
padrão de 256MB. Rodar com os 10 arquivos de Empresas de uma vez (~5,5GB de texto ao todo)
provavelmente estouraria a RAM do VPS de verdade, não só o limite do PHP.

**Corrigido invertendo a ordem de processamento**: primeiro filtra `Estabelecimentos*.csv` (bem
mais seletivo — só CNAE + situação ativa) e guarda em memória só os poucos milhares de CNPJs que
batem no filtro; só DEPOIS varre `Empresas*.csv`, mas ignorando (sem guardar) qualquer linha cujo
`cnpj_basico` não esteja entre os já filtrados. Memória passa a ser proporcional ao número de
**leads**, não ao cadastro nacional inteiro. Testado com fixtures sintéticas simulando o layout
real (CNAE certo/errado, situação ativa/baixada) — filtra, resolve razão social e município
corretamente, sem carregar nada além do necessário.

**Base populada de verdade**: rodado em produção contra o dataset completo de agosto/2026 (todos
os 10 arquivos de Estabelecimentos/Empresas) — 259.298 leads filtrados e importados em
`leads_prospeccao` (259.298 processados, 0 ignorados). `/master/prospeccao` passou de vazio pra
uma base nacional real; filtro por cidade testado com "Feira de Santana" (726 leads elegíveis com
e-mail só nessa cidade).

## Convite de prospecção ganhou seção de apresentação do sistema completo

Pedido do usuário: o e-mail de convite pro diretório grátis (`EmailService::convitePropeccao()`)
falava só do diretório — pediu pra também convidar a empresa a conhecer o FixaOS completo e
mostrar o que o sistema oferece pra organizar o dia a dia da assistência.

- **Segundo bloco no e-mail**, depois de um divisor visual: "De quebra, aproveite pra conhecer —
  O FixaOS também organiza o dia a dia da sua assistência", com 4 benefícios (Ordens de Serviço,
  Financeiro, Estoque e PDV, WhatsApp automático) — mesma lista/tom já usado no card "Conheça o
  FixaOS completo" de `empresa/perfil_publico.php`, pra manter a mensagem de marketing consistente
  entre os dois pontos de contato (e-mail frio e o convite dentro do próprio painel).
- **CTA novo**: botão outline "▶ Ver demonstração ao vivo, sem cadastro" apontando pra `/demo` —
  rota pública já existente (login automático numa conta demo, sem pedir cadastro), visualmente
  diferente do botão laranja sólido do diretório (outline azul-marinho) pra não competir pela
  atenção — o diretório continua sendo o CTA principal (goal primário do disparo), a demonstração
  é secundária ("de quebra").
- Link de descadastro no rodapé continua valendo pro e-mail inteiro (LGPD) — cancelar o convite
  cancela os dois blocos, não dá pra optar só por um.

**Remetente fixo em `suporte@fixaos.com.br`**: testado enviando de verdade pro e-mail pessoal do
usuário e pro `suporte@fixaos.com.br` (via `EmailService::convitePropeccao()` chamado direto por
`php -r`, sem passar pela fila de leads — não consome a cota diária nem marca lead nenhum como
contatado). `EmailService::send()` ganhou `$fromEmail`/`$fromName` opcionais (sobrescrevem
`from_email`/`from_name` de `config/email.php` só pra esse envio) porque convite frio pra fora
faz mais sentido vindo de "suporte" do que do endereço genérico de contato do sistema — sem
mexer no remetente padrão usado pelos outros e-mails (boas-vindas, confirmação de cadastro etc.).

## Disparo diário automático, misturando estados

Pedido do usuário depois de validar o disparo manual: rodar o convite todo dia sozinho, sem
precisar clicar em "Disparar agora", e sem concentrar tudo num estado só mesmo que ele tenha
muito mais leads acumulados que os outros (ex.: SP tem ordens de grandeza mais leads que estados
menores — sem misturar, um disparo "pega os mais antigos" ficaria dias inteiros só em SP).

- **`App\Services\Prospeccao\DisparoService`** (novo) — extrai o núcleo de envio que antes vivia
  só dentro de `MasterController::prospeccaoDisparar()`, pra ser compartilhado entre o botão
  manual e a rotina automática, os dois descontando do **mesmo contador** de limite diário
  (`email_convite_enviado_em >= CURDATE()`) — não tem como os dois juntos passarem do limite.
  - `dispararFiltrado()` — o que o botão manual já fazia: ordem simples pelos mais antigos,
    respeitando o filtro atual da tela (status/cnae/uf/cidade).
  - `dispararMisturandoUf()` — novo, usado só pela rotina automática: busca até 2000 leads
    elegíveis (teto generoso, não a base inteira) ordenados por UF, agrupa em memória por estado,
    e escolhe por **round-robin** (1 de cada UF por volta, repete a volta até bater a quantidade
    pedida) — garante que o lote do dia sempre mistura vários estados, mesmo que um deles tenha
    dez vezes mais leads elegíveis que os outros. Testado com dados sintéticos (grupos de tamanho
    bem diferente) confirmando a intercalação antes de rodar em produção.
- **`scripts/disparar_prospeccao_diario.php`** — script de cron (mesmo padrão de
  `processar_lembretes_agenda.php`: `BASE_PATH` + autoload manual, timezone de `config/app.php`,
  print de uma linha de resumo). Recomendado rodar 1x/dia:
  ```
  0 9 * * * php /var/www/fixaos/scripts/disparar_prospeccao_diario.php >> /var/www/fixaos/storage/logs/prospeccao_cron.log 2>&1
  ```
  **Sem cron real configurado, o disparo automático simplesmente não acontece** — diferente do
  poller de lembretes/notificações, não existe fallback throttled embutido no tráfego do app (não
  faz sentido aqui: disparo de e-mail não deveria depender de alguém estar com o painel aberto).
- **Botão manual continua existindo**, agora pra casos específicos (ex.: focar só numa cidade
  como "Feira de Santana", como no teste inicial) — a rotina automática cobre o volume diário
  geral (nacional, sem filtro de cidade), o botão cobre disparo direcionado avulso.

## Acompanhamento pós-cadastro no diretório

Pedido do usuário depois de discutir a estratégia do funil (e-mail frio → cadastro grátis →
sistema completo): faltava um segundo toque — hoje quem se cadastra pelo `/diretorio/cadastrar`
(conta `tipo_conta='diretorio'`) só vê o convite pro sistema completo se abrir o próprio painel
de novo por conta própria. Pedido explícito de tom: **"sem ícones de florzinha nem coração, algo
sério e muito profissional"** — diferente dos outros templates do `EmailService` (que usam
🎉/💙), este é deliberadamente sóbrio: sem emoji nenhum, tipografia reta, botões retangulares
sem gradiente, saudação/despedida formais ("Prezado(a)"/"Atenciosamente").

- **Migration `041_empresas_diretorio_followup.sql`** — `empresas.diretorio_publicado_em`
  (marca a primeira vez que o perfil vira público de verdade) e
  `empresas.diretorio_followup_enviado_em` (nunca reenvia pra mesma empresa).
- **`EmpresaController::salvarPerfilPublico()`** grava `diretorio_publicado_em` só na transição
  `listagem_publica` 0→1 (compara o valor atual, lido antes do UPDATE, com o novo) — e só se
  ainda não tinha sido gravado antes, então não importa quantas vezes a empresa edite o perfil
  depois, a data continua sendo a da primeira publicação real.
- **`EmailService::diretorioFollowUp()`** — novo template formal (ver seção acima do porquê do
  tom); usa `razao_social` (que pra conta `tipo_conta='diretorio'` guarda o nome da PESSOA que se
  cadastrou, não da empresa — `cadastrarSalvar()` grava assim no passo 1, antes do passo 2
  preencher `nome_fantasia` com o nome da empresa de fato) como saudação, e `nome_fantasia` como
  o nome da empresa no corpo do texto. Dois botões (não um CTA único): "Acessar demonstração"
  (`/demo`) e "Consultar planos" (`/planos`) — sem hierarquia visual forte entre os dois (ambos
  formais, um sólido e um outline), diferente do convite de prospecção onde o diretório é
  claramente o CTA principal — aqui a empresa já está dentro do sistema, os dois caminhos têm
  peso parecido.
- **`scripts/disparar_followup_diretorio.php`** — cron 1x/dia (mesmo padrão dos outros scripts
  de disparo), `followup_dias` configurável em `config/prospeccao_email.php` (padrão 5). Só
  empresas `tipo_conta='diretorio'` — quem já é `tipo_conta='completo'` (assina o sistema) já vê
  o card "Conheça o FixaOS completo" dentro do próprio painel, não precisa deste e-mail.
- **Sem link de descadastro** (diferente do convite de prospecção) — este não é e-mail frio pra
  desconhecido, é acompanhamento pra quem já tem conta e login no sistema; mesmo padrão de
  `boasVindas()`/`perfilReivindicado()`, que também não têm unsubscribe.

## Rampa de volume: de 20 pra 1.000 e-mails/dia

Pedido do usuário: 20/dia é pouco alcance útil, porque boa parte da base de 259 mil leads (CNPJ
ativo nas CNAEs do setor) não vira contato aproveitável — segmento errado dentro da CNAE, empresa
sem atividade de verdade apesar de "ATIVA" no cadastro (comum no Brasil: fechar um CNPJ de
verdade custa dinheiro/burocracia, então muita empresa parada nunca é formalmente baixada), etc.
Não tem como filtrar melhor isso no lado da Receita (`situacao_cadastral='02'` já é o sinal mais
forte que existe nos dados abertos) — o jeito é aumentar o volume.

**Risco identificado e resolvido**: pular direto de 20 pra 1.000/dia seria arriscado — não pela
qualidade dos leads, mas pela **reputação de envio**: um salto brusco de volume sem histórico
costuma ser tratado como spam pelos provedores (Gmail/Outlook), o que prejudicaria também os
e-mails REAIS do sistema (confirmação de cadastro, recibos), que usam o mesmo domínio/SMTP.
Perguntado ao usuário — optou pela rampa em vez do salto direto.

- **`config/prospeccao_email.php`** ganhou `rampa` (array dia→limite) + `rampa_inicio` (data
  âncora): 20 → 60 → 150 → 300 → 500 → 750 → 1.000, alcançando 1.000/dia em 15 dias — degraus
  maiores e mais frequentes que o "dobrar a cada poucos dias" original, só que ainda gradual. Dias
  além do último degrau mantêm 1.000 (não continua subindo sozinho). Testado simulando várias
  datas contra a tabela antes de aplicar.
- **`DisparoService::limiteDiarioAtual(array $emailCfg): int`** — novo método central, calcula o
  degrau do dia a partir de `rampa_inicio`; cai pra `limite_diario` fixo (compatibilidade) se a
  config não tiver `rampa`/`rampa_inicio`. Os três lugares que liam `limite_diario` direto
  (`MasterController::prospeccao()`/`prospeccaoDisparar()` e
  `scripts/disparar_prospeccao_diario.php`) passaram a chamar esse método — nenhum deles calcula
  o limite por conta própria mais, evita duas contas divergentes.
- **Continua reversível**: se a taxa de bounce ou reclamação de spam subir em algum degrau, é só
  editar os valores de `rampa` (baixar o degrau atual ou parar de subir) — não precisa mexer em
  código, só na config.

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

**`config/app.php` NUNCA entra em `git checkout github/<branch> -- <arquivos>`.**
Diferente de `config/database.php`/`email.php`/`google.php`/`infinitepay.php`/
`whatsapp.php` (esses sim gitignorados), `config/app.php` está versionado —
mas guarda valores que são diferentes em cada ambiente (`url`, `debug`, `key`),
com o valor de **desenvolvimento** commitado (`url` = `http://localhost/...`,
`debug` = `true`). Um `git checkout` desse arquivo no VPS sobrescreve a URL e o
debug de produção pelos valores de dev — já aconteceu uma vez (2026-08-09):
quebrou todo link do site (apontando pra `localhost`) e ligou `display_errors`
em produção. Precisando mudar algo em `config/app.php` que deveria valer pro
VPS também (ex.: `version`), edite o arquivo direto lá (`nano
/var/www/fixaos/config/app.php`), nunca copiando do git.

**`lib/dompdf/vendor/` não está no git** (`App\Services\PdfService`, usada pelo PDF de
orçamento/laudo da OS e pelo "Baixar em PDF" do manual — `lib/dompdf/autoload.inc.php` exige
`vendor/autoload.php`). Só os metadados (`AUTHORS.md`/`LICENSE.LGPL`/`README.md`/`VERSION`)
entraram no commit `55f70b8` ("Snapshot da VPS de produção"), a lib de verdade não — provável
que o processo de importar o snapshot tenha pulado essa pasta. **Funciona em produção** (o
diretório existe no VPS fora do git, confirmado 2026-08-09), mas não dá pra gerar/testar PDF
num checkout novo deste repo (ex.: sandbox de dev) sem instalar o Dompdf ali manualmente
primeiro. Vendorizar isso de verdade no git é uma melhoria pendente, não urgente.
