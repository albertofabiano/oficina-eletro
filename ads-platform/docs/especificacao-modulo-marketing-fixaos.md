# Especificação: Módulo Marketing no FixaOS

Documento para implementar, **dentro do FixaOS**, o módulo de gestão de tráfego pago
(Meta Ads, depois Google Perfil da Empresa e Google Ads). Ele consolida o que foi
construído e testado no protótipo `ads-platform` (Next.js + Supabase), que continua no ar
como referência até o módulo ficar pronto.

> **Para o Claude Code que for implementar:** leia o documento inteiro antes de começar,
> confira o código do FixaOS e **apresente um plano por etapas para aprovação humana**
> antes de escrever código. Adapte nomes e estrutura às convenções do FixaOS; as **regras
> inegociáveis** (seção 2) e os **números** das regras (seções 8 e 9) não mudam.

---

## 1. Objetivo e escopo

- Módulo **opcional por empresa** ("Marketing"), ativado quando o cliente quiser.
  Empresas sem o módulo não veem nada nem geram nenhuma chamada externa.
- Fase 1: **Meta Ads**: coleta diária, painel, alertas, sugestões de otimização e
  **fila de aprovação** (nada é alterado sem aprovação humana).
- Fase 2: **Google Perfil da Empresa**: visualizações, ligações, rotas, cliques no site,
  avaliações (ler e responder via fila de aprovação).
- Fase 3: **ROI real com as OS**: ligar a origem do cliente às OS e ao faturamento
  ("a campanha X gerou 12 OS e R$ 4.300").
- Fora do escopo por enquanto: criação de campanhas, Google Ads, sites por template.

## 2. Regras inegociáveis

1. **Nunca alterar status ou orçamento de campanha sem aprovação humana registrada no banco.**
   Toda escrita nas APIs de anúncio passa pela fila de aprovação (seção 9). Regras e
   telas só **sugerem**; o único código que chama a escrita na plataforma é o **executor**.
2. **Modo simulação (`DRY_RUN`)**, variável de ambiente, **ligado por padrão**. Quando ativo,
   nenhuma escrita é enviada às plataformas; a aprovação é registrada como executada
   "em simulação". Leituras (coleta) funcionam normalmente. A interface mostra um selo
   "Modo simulação".
3. **Tokens de acesso criptografados no banco.** Nunca logar, nunca retornar ao navegador,
   nunca colocar em mensagens de erro. A chave de criptografia fica **fora do banco**
   (arquivo de ambiente da VPS). Ver seção 5.4.
4. **Dinheiro sempre em centavos inteiros** (inteiro/bigint), nunca float. Conversão do
   texto da API ("12.34") para centavos **sem passar por float**.
5. **Datas no fuso `America/Sao_Paulo`.** "Hoje", "ontem" e os dias das métricas usam esse
   calendário.
6. **Toda coleta recoleta os últimos 7 dias** (a atribuição da Meta muda depois).
7. **Respeitar o limite de uso da Meta** (header `x-business-use-case-usage`):
   desacelerar acima de 75% e esperar o tempo de bloqueio informado.
8. **Plataformas implementam uma interface comum** (`AdPlatform`), para adicionar Google
   depois sem mexer no resto.
9. **Isolamento:** falha, lentidão ou erro do módulo Marketing **nunca** pode travar ou
   atrasar o restante do FixaOS (OS, caixa etc.). Coleta e execução rodam em segundo plano,
   com limite de tempo.

## 3. Arquitetura dentro do FixaOS

- **Mesmo banco e mesmo login** do FixaOS; tabelas novas com prefixo `mkt_` (ou o padrão
  do projeto), todas com `empresa_id` (multiempresa) e isoladas por empresa em toda consulta.
- **Tarefas agendadas via cron da VPS** (ou o agendador que o FixaOS já usar):
  - `06:00` (America/Sao_Paulo): coleta de todas as empresas com módulo ativo → gera
    sugestões → executa aprovações pendentes de execução.
  - Opcional a cada 15 min: executar pedidos recém-aprovados (para não esperar até 06:00).
  - Trava de execução (lock) para duas execuções nunca rodarem ao mesmo tempo.
- **Botão "Sincronizar agora"**: roda a coleta só da empresa, com intervalo mínimo de
  60 segundos (exceto se houver conta conectada que ainda nunca foi coletada).
- **SDK oficial da Meta para PHP** (`facebook/php-business-sdk`) ou chamadas HTTP diretas
  à Graph API; em ambos os casos, encapsular num cliente próprio (seção 5).
- **Modelo agência** (seção 5.1): uma conexão central da FixaOS; os clientes não lidam
  com tokens nem com "API".

## 4. Modelo de dados

Tipos indicativos (adaptar ao banco do FixaOS). Dinheiro em `BIGINT` de centavos.

**`mkt_ad_accounts`**: contas de anúncio vinculadas a uma empresa
| coluna | tipo | observação |
|---|---|---|
| id | PK | |
| empresa_id | FK | |
| platform | enum(`meta`,`google_ads`,`fake`) | `fake` = conta de demonstração |
| external_id | varchar | Meta: `act_<números>` |
| name | varchar | nome vindo da plataforma |
| currency | char(3) | só `BRL` é aceito |
| status | enum(`active`,`disconnected`) | |
| last_synced_at | datetime null | |
| last_sync_error | varchar(500) null | mensagem sem token; limpa ao coletar com sucesso |
| created_at | datetime | |
| **único** (empresa_id, platform, external_id) | | |

**`mkt_credentials`**: tokens criptografados (modelo agência: normalmente **uma** linha global)
| coluna | tipo | observação |
|---|---|---|
| id | PK | |
| scope | enum(`global`,`empresa`) | `global` = token do usuário do sistema da FixaOS |
| empresa_id | FK null | só quando scope=`empresa` |
| platform | enum | |
| token_ciphertext | blob | criptografia autenticada (libsodium `secretbox` ou AES-256-GCM) |
| token_nonce | blob | |
| key_version | int | permite trocar a chave de criptografia |
| created_at / updated_at | datetime | |

Nenhuma tela ou endpoint lê `token_ciphertext`; só o serviço de coleta/execução descriptografa em memória.

**`mkt_campaigns`**
| coluna | tipo | observação |
|---|---|---|
| id | PK | |
| empresa_id, ad_account_id | FK | |
| external_id | varchar | id da campanha na Meta |
| name | varchar | |
| status | enum(`active`,`paused`,`archived`) | |
| daily_budget_cents | bigint null | null quando o orçamento é por conjunto de anúncios |
| synced_at | datetime | |
| **único** (ad_account_id, external_id) | | |

**`mkt_daily_insights`**: uma linha por campanha por dia
| coluna | tipo |
|---|---|
| campaign_id + date | **PK composta** (upsert: recoleta sobrescreve) |
| empresa_id | FK |
| spend_cents | bigint ≥ 0 |
| impressions, clicks | bigint ≥ 0 |
| leads | int ≥ 0 |
| collected_at | datetime |

**`mkt_action_requests`**: fila de aprovação
| coluna | tipo | observação |
|---|---|---|
| id | PK | |
| empresa_id, campaign_id | FK | |
| action_type | enum(`pause_campaign`,`resume_campaign`,`update_daily_budget`) | |
| payload | json | orçamento: `{"daily_budget_cents": 3200, "previous_daily_budget_cents": 4000}` (inteiros > 0) |
| reason | text | explicação em português mostrada ao usuário |
| source | enum(`rule`,`user`) | |
| rule_id | varchar null | ex.: `pause-no-leads` |
| status | enum(`pending`,`approved`,`rejected`,`executed`,`failed`) | |
| requested_by, requested_at | | |
| decided_by, decided_at | | obrigatórios quando status ≠ pending |
| executed_at | datetime null | obrigatório em executed/failed |
| dry_run | bool null | true quando executado em simulação |
| error | varchar null | |
| **único** (campaign_id, action_type) **enquanto pending** | | no MySQL: coluna gerada `pending_key` = `IF(status='pending', CONCAT(campaign_id,':',action_type), NULL)` com índice único |

**`mkt_audit_log`**: registro de tudo: conexão/desconexão de conta, coleta, decisão,
execução. Campos: empresa_id, usuario_id null, action, entity_type, entity_id, details (json **sem token**), created_at.

**Transições permitidas** (validar no código **e**, se possível, no banco via trigger):
`pending → approved | rejected` · `approved → executed | failed`. Qualquer outra é erro.
A decisão (quem/quando) não pode ser alterada depois de gravada.

## 5. Integração Meta Ads

### 5.1 Autenticação: modelo agência (recomendado)
- A FixaOS tem **um app** na Meta e **um usuário do sistema** no portfólio empresarial
  **Fixaos**, com **um token** (permissões `ads_read` + `ads_management`, validade "Nunca").
  Esse token fica em `mkt_credentials` com `scope = global`.
- **Cada cliente** só precisa, na Meta, **adicionar o portfólio Fixaos como parceiro** da
  conta de anúncios dele (Configurações do negócio → Contas de anúncios → Atribuir
  parceiro → ID do portfólio da FixaOS) e depois **atribuir a conta ao usuário do sistema**
  pelo lado da FixaOS. No FixaOS, o cliente só informa o **ID da conta de anúncios**.
- Alternativa futura: botão "Conectar com Facebook" (OAuth) para quem preferir.

### 5.2 Configuração já feita (valores públicos, não são segredos)
| item | valor |
|---|---|
| Portfólio empresarial | Fixaos, `business_id` 1333346049856525 |
| App | "Trafego Pago FixaOS", ID **1881955869841231** (modo desenvolvimento) |
| Usuário do sistema | `trafego-pago`, ID 61594762537527, Admin, com acesso total ao app e à conta abaixo |
| Conta de anúncios | `act_1563887931891870` (BRL, America/Sao_Paulo), criada via Instagram |

**Pendência conhecida:** a Graph API responde `(#200) Ad account owner has NOT grant
ads_management or ads_read permission` para essa conta. Próximos passos: autorizar a conta no app
(developers.facebook.com → app → Configurações do app → Avançado → **Contas de anúncios**)
e testar no Explorador da Graph API. Se continuar, criar uma **conta de anúncios nova
diretamente no portfólio Fixaos** (a atual foi criada pelo Instagram e se comporta de forma
diferente). O token usado nos testes apareceu num print e **deve ser anulado e regenerado**.

### 5.3 Chamadas (Graph API, versão fixada, ex. v24.0 ou mais nova)
- **Validar conta ao conectar:** `GET act_<id>?fields=id,name,currency,timezone_name,account_status`
  - Recusar se `currency ≠ BRL` ("só contas em Real"), `timezone_name ≠ America/Sao_Paulo`,
    ou `account_status` 2 (desativada) / 101 (encerrada).
  - Normalizar entrada: aceitar `123`, `act_123`, `ACT_123` → `act_123`; exigir 5–20 dígitos.
- **Campanhas:** `GET act_<id>/campaigns?fields=id,name,status,daily_budget&limit=500`
  com `effective_status=["ACTIVE","PAUSED","ARCHIVED","DELETED","IN_PROCESS","WITH_ISSUES"]`
  (sem esse filtro a Meta omite arquivadas/excluídas e elas ficariam "ativas" no banco).
  - Status: `ACTIVE→active`, `PAUSED→paused`, demais→`archived`.
  - `daily_budget` vem em centavos (texto inteiro); ausente ⇒ `null`.
- **Métricas diárias:** `GET act_<id>/insights?level=campaign&time_increment=1&time_range={"since":"AAAA-MM-DD","until":"AAAA-MM-DD"}&fields=campaign_id,date_start,spend,impressions,clicks,actions&limit=500`
  - `spend` "37.89" → 3789 centavos (conversão por texto, arredondando o 3º decimal).
  - **Leads:** usar `actions[action_type="lead"]`; se não existir, somar
    `onsite_conversion.lead_grouped` + `offsite_conversion.fb_pixel_lead` (nunca somar com `lead`, que já é o agregado).
- **Paginação:** seguir `paging.next` até acabar (limite de segurança: 200 páginas). Só
  seguir URLs que comecem com `https://graph.facebook.com/`.
- **Escritas (só o executor):** `POST <campaign_id>` com `status=PAUSED|ACTIVE` ou
  `daily_budget=<centavos>` (inteiro > 0; recusar antes de chamar se não for).

### 5.4 Segurança do token (lições do protótipo)
- Criptografia autenticada; chave em variável de ambiente (`MKT_ENCRYPTION_KEY`), nunca no banco nem no Git.
- **Erros do SDK carregam a URL com o token.** Todo erro da Meta deve ser convertido para
  um erro próprio contendo **só** `message`, `code`, `error_subcode` e o header de uso;
  descartar URL, parâmetros e o objeto original antes de logar ou exibir.
- Desativar qualquer "crash reporter" do SDK (o SDK de Node tinha um ligado por padrão).
- Formulários com token: campo **não** pode ser `type=password` (o navegador passa a
  tratar como login e preenche e-mail/senha salvos); usar texto mascarado por CSS e
  `autocomplete="off"`. Nunca devolver o token ao formulário depois de salvo.

### 5.5 Limite de uso (rate limit)
- Ler `x-business-use-case-usage` em **toda** resposta (inclusive de erro): JSON
  `{"<id>":[{"call_count":N,"total_cputime":N,"total_time":N,"estimated_time_to_regain_access":M}]}`.
- Uso = maior entre `call_count`, `total_cputime`, `total_time` de todos os itens.
- Espera antes da próxima chamada: **0** até 75%; acima, linear até **60 s** em 100%
  (`(uso−75)/25 × 60 s`); se `estimated_time_to_regain_access > 0`, esperar esses **minutos**.
- Códigos de limite: 4, 17, 32, 613, 80000–80014 → mensagem "Limite de uso da API da Meta
  atingido. A coleta será refeita na próxima sincronização."

### 5.6 Mensagens de erro (português + detalhe da Meta)
Mostrar a explicação **e** o detalhe original: `… Detalhe da Meta: "<message>" (código <code>/<subcode>).`
- 190 → "Token de acesso da Meta inválido ou expirado. Gere um novo token e reconecte a conta."
- 10 e 200–299 → "O token não tem permissão para esta conta de anúncios. Confira as permissões ads_read e ads_management."
- 100/33 → "Conta de anúncios não encontrada ou sem acesso para este token."
- Sem resposta da Meta (rede) → "Falha de comunicação com a API da Meta."

## 6. Coleta

- Por conta ativa: 1ª coleta importa **60 dias**; as seguintes, **7 dias** (de hoje−6 a hoje, fuso SP).
- Ordem: listar campanhas → upsert em `mkt_campaigns` → buscar métricas → upsert em
  `mkt_daily_insights` (chave campanha+dia; ignorar linhas de campanhas desconhecidas) →
  `last_synced_at = agora`, `last_sync_error = null` → registrar no audit log (período, nº de campanhas, nº de linhas).
- Falha numa conta **não interrompe** as outras; grava `last_sync_error` (sem token).
- Depois da coleta: gerar sugestões (seção 8) e rodar o executor (seção 9).

## 7. Painel, métricas e alertas

- Período: 7, 14 ou 30 dias **terminando ontem** (dia de hoje incompleto fica de fora),
  comparado ao período anterior de mesmo tamanho (variação %).
- Indicadores: investimento, leads, **custo por lead** (investimento ÷ leads, arredondado;
  "—" se 0 leads), CTR (cliques ÷ impressões), custo por clique, CPM.
- Gráfico diário: barras = investimento, linha = leads.
- Tabela de campanhas ordenada por investimento, com filtro Todas / Ativas / Pausadas;
  página de detalhe por campanha (indicadores, gráfico, tabela dia a dia, histórico de sugestões).
- **Alertas** (só leitura, campanhas ativas, no período exibido):
  - Crítico "Gastando sem gerar leads": investimento ≥ **R$ 50,00** e 0 leads.
  - Atenção "Custo por lead acima da média": CPL da campanha > **1,5×** o CPL da conta.

## 8. Regras de sugestão (otimização)

Janela: **últimos 7 dias completos** (ontem e 6 dias antes). CPL da conta = investimento
total ÷ leads totais da conta na janela. Só campanhas **ativas**. **No máximo uma sugestão
por campanha; a primeira regra que casar vence** (pausar > reduzir > aumentar).

| regra (`rule_id`) | condição | ação |
|---|---|---|
| `pause-no-leads` | 0 leads e investimento ≥ **R$ 50,00** (5.000 centavos) | pausar campanha |
| `reduce-budget-high-cpl` | orçamento diário conhecido, CPL da campanha > **1,5×** CPL da conta | orçamento × **0,8** (−20%), arredondado, **mínimo R$ 5,00**; só se ficar menor que o atual |
| `increase-budget-low-cpl` | ≥ **5 leads**, CPL ≤ **0,6×** CPL da conta e uso do orçamento ≥ **80%** (investimento ÷ (orçamento diário × 7)) | orçamento × **1,2** (+20%), arredondado |

Texto do motivo (exemplos): "Gastou R$ 62,40 nos últimos 7 dias sem gerar nenhum lead." ·
"Custo por lead de R$ 38,00, acima de 1,5x a média da conta (R$ 20,00)." (número com vírgula, formato pt-BR).

**Não repetir sugestão:** não criar se já existe uma **pendente** da mesma campanha+ação,
nem se uma igual foi **rejeitada nos últimos 7 dias**.

## 9. Fila de aprovação e executor

- Tela **Aprovações**: pendentes (ação, campanha, motivo, "sugerida há X") com botões
  **Aprovar** / **Rejeitar**; histórico com quem decidiu, quando, resultado, simulação ou erro.
- Decidir grava `decided_by`/`decided_at` e audit log. Mensagens após decidir:
  - aprovado em simulação: "Aprovado e registrado em modo simulação — nada foi enviado à Meta."
  - aprovado e executado: "Aprovado e aplicado na plataforma."
  - rejeitado: "Sugestão rejeitada. Ela não será sugerida de novo nos próximos 7 dias."
  - falhou: "Aprovado, mas a execução falhou. Veja o motivo no histórico."
- **Executor** (única parte que escreve na Meta), para cada pedido **approved**:
  1. valida o payload (orçamento inteiro > 0) **mesmo em simulação**;
  2. carrega a campanha e a conta; se não existir → `failed`;
  3. se `DRY_RUN` → `executed` com `dry_run = true`, **sem chamar a Meta**;
  4. senão chama a Meta; sucesso → atualiza a campanha local e marca `executed`
     (`dry_run = false`); erro → `failed` com a mensagem (sem token).
- Botão sugerido: executar logo após a aprovação (além do cron).

## 10. Telas do módulo

1. **Marketing › Painel**: indicadores, gráfico, alertas, top campanhas, "Sincronizar agora",
   selo "Modo simulação", selo "Conta de demonstração" quando for o caso.
2. **Marketing › Campanhas**: lista com filtros e página de detalhe.
3. **Marketing › Aprovações**: contador de pendentes no menu.
4. **Marketing › Configurações**: contas conectadas (nome, plataforma, ID, status
   Conectada/Com erro/Desconectada, última coleta, último erro), conectar conta Meta
   (só ID no modelo agência), desconectar, conta de demonstração, e passo a passo para o
   cliente dar acesso (parceiro na Meta / administrador no Google).
- Só **dono/administrador** da empresa conecta, desconecta e aprova (definir com o dono do produto
  se "técnico" também pode aprovar). Textos da interface em português; código em inglês.

## 11. Fase 2: Google Perfil da Empresa (planejar depois da fase 1)

- Projeto Google Cloud da conta **fixaosbr@gmail.com**; pedir acesso à Business Profile
  API (formulário do Google; pode levar semanas; exige perfil verificado ativo e site).
- **Modelo agência:** o cliente adiciona `fixaosbr@gmail.com` como **Administrador** do
  Perfil da Empresa dele; uma conexão OAuth da FixaOS enxerga todos.
- Dados: Business Profile Performance API (visualizações Busca/Maps, ligações, rotas,
  cliques no site, mensagens; diário), termos de busca, avaliações (listar e responder;
  **resposta passa pela fila de aprovação**).
- Não existe "visitas físicas na loja"; os indicadores equivalentes são rotas e ligações.

## 12. Fase 3: ROI com as OS

- Campo **"Como conheceu / origem do cliente"** no cadastro do cliente e/ou na OS
  (Google, Instagram/Facebook, anúncio, indicação, passante, outro). Opcional: campanha.
- Relatórios: OS abertas, OS fechadas e faturamento por origem e por campanha; custo por OS.

## 13. Testes obrigatórios (com dados fictícios)

- Conversão texto→centavos ("37.89", "0.105", "7", inválidos); formatação R$ pt-BR.
- Datas: "hoje" em São Paulo perto da meia-noite UTC; janelas de 7/14/30 dias terminando ontem.
- Cada regra de sugestão (casa / não casa / limites exatos / piso de R$ 5 / prioridade).
- Alertas; deduplicação e cooldown de 7 dias.
- Transições da fila (inclusive proibidas); executor em simulação (não chama a Meta) e real
  (com cliente Meta falso); payload inválido falha mesmo em simulação.
- Cliente Meta com respostas simuladas: paginação, mapeamento de status, leads, erros 190/200/100-33,
  limite de uso (atrasos em 50/90/100% e bloqueio), e **erro nunca contém o token**.
- Isolamento multiempresa: uma empresa nunca lê/decide dados de outra.
- Criptografia: token salvo ≠ texto puro; descriptografa com a chave; troca de chave (`key_version`).

## 14. Etapas sugeridas (cada uma com aprovação antes de começar)

1. Tabelas + criptografia + conta de demonstração (plataforma simulada) + painel lendo do banco.
2. Coleta agendada (cron) com a plataforma simulada; "Sincronizar agora".
3. Regras de sugestão + fila de aprovação + executor com `DRY_RUN`.
4. Campanhas (lista e detalhe).
5. Meta real (modelo agência) + Configurações. Testar lendo a conta real com `DRY_RUN` ligado.
6. Desligar `DRY_RUN` só com decisão explícita do dono, depois de conferir sugestões reais.
7. Fases 2 (Google Perfil) e 3 (ROI com OS).

Referência de código: pasta `ads-platform/` do repositório `albertofabiano/oficina-eletro`
(`src/lib/ads/meta/`, `src/lib/optimization/rules.ts`, `src/lib/queue/`, `src/lib/sync/`,
`supabase/migrations/`, testes `*.test.ts` e `tests/db/`).
