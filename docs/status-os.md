# FixaOS — Sistema de Status de OS (spec técnica p/ auditoria)

> Descrição factual de como funcionam os status de Ordem de Serviço e a **garantia**. Objetivo: uma IA (ou pessoa) ler e **encontrar furos/edge-cases por conta própria**. Estado do código em 2026-07-13. Sistema **multi-tenant**: tudo escopado por `empresa_id`.

---

## 1. Tabela `os_status`

| Coluna | Tipo | Papel |
|---|---|---|
| `id` | int | PK |
| `empresa_id` | int | dono (isolamento multi-tenant) |
| `nome` | varchar(60) | rótulo exibido (cosmético) |
| `cor` / `cor_fonte` | varchar | cor do badge / do texto (cosmético) |
| `ordem` | tinyint | ordenação do fluxo; reordenável arrastando |
| `tipo` | enum | **motor de comportamento** (§2) |
| `permite_fechar` | tinyint(1) | mostra o botão "Fechar OS" quando a OS está nele |
| `sem_valor` | tinyint(1) | status "pré-orçamento": bloqueia adicionar serviço/peça |
| `bloqueado` | tinyint(1) | 1 = status NATIVO (travado, §3) |
| `codigo` | varchar(30) | discriminador dos nativos (`pronto`, `fechado`, …) |

`tipo` é um ENUM com **exatamente 6 valores**: `aberta`, `em_andamento`, `aguardando`, `concluida`, `cancelada`, `entregue`. Não existe tipo `garantia`.

---

## 2. Comportamento por `tipo`

| tipo | Dispara |
|---|---|
| `aberta` | etapa; conta como "em aberto" |
| `em_andamento` | etapa; aparece nas OS ativas |
| `aguardando` | etapa; **não** conta como atrasada |
| `concluida` | carimba `data_conclusao` ao entrar; **gera receita** se `valor_total>0` |
| `entregue` | carimba `data_entrega`; **gera receita** se `valor_total>0` |
| `cancelada` | finaliza sem receita |

- **Receita (financeiro):** status com `tipo IN ('concluida','entregue')` e `valor_total > 0`; usa `data_conclusao` como data e `situacao_pagamento` (pendente/parcial/pago).
- **Atrasada:** `data_previsao < agora` e `tipo NOT IN ('concluida','entregue','cancelada')`.

---

## 3. Status NATIVOS (`bloqueado=1`) — 6 por empresa

Esqueleto igual em toda empresa (existente e futura), para o núcleo não quebrar:

| codigo | nome | tipo | permite_fechar | sem_valor | ordem |
|---|---|---|---|---|---|
| `orcamento` | Orçamento | aberta | 0 | 1 | 1 |
| `em_analise` | Em análise | em_andamento | 1 | 1 | 2 |
| `aguardando_pecas` | Aguardando peças | aguardando | 1 | 0 | 3 |
| `pronto` | Pronto | concluida | 1 | 0 | 4 |
| `fechado` | Fechado | entregue | 0 | 0 | 5 |
| `sem_conserto` | Sem Conserto | cancelada | 1 | 1 | 7 |

(Gap na ordem 6 — havia um "Garantia" removido; a garantia não usa mais status próprio, §6.)

**Trava dos nativos** (`OsStatusController`): só edita `cor`, `cor_fonte`, `permite_fechar`, `sem_valor`; `nome`/`tipo`/`ordem` fixos; **não pode excluir**. Status **personalizados** (`bloqueado=0`): a empresa cria à vontade, com **qualquer `tipo`**, edita, exclui e **reordena** (arrastar). Nova empresa nasce com os 6 nativos + "Em Reparo" (em_andamento, ordem 8, editável).

---

## 4. Colunas de `ordens_servico` relevantes

`status_id` (FK → os_status, **RESTRICT**), `tipo_servico` (enum: orcamento/garantia/conserto/manutencao/instalacao), `os_origem_id` (aponta pra OS original quando é retorno de garantia), `garantia_dias`, `garantia_ate` (date), `data_conclusao`, `data_entrega`, `valor_total`, `situacao_pagamento`.
Transições em `os_historico` (`empresa_id`, `os_id`, `status_anterior_id`, `status_novo_id`, `criado_em`) — **sem FK** para os_status.

---

## 5. Fluxos

- **Mudar status (`atualizarStatus`):** valida `sem_valor` (não deixa entrar num status `sem_valor=1` se a OS já tem valor > 0); `concluida` carimba `data_conclusao`.
- **Fechar OS (`fechar()`):** se o status atual é `tipo='cancelada'` → mantém (sem receita). Senão → move para o status `codigo='fechado'` (senão 1º `tipo='entregue'`, senão 1º `tipo='concluida'`); grava pagamento, desconto, taxa de cartão, `garantia_dias`/`garantia_ate`, `data_entrega`.
- **Reabrir (`reabrir()`):** pega `status_anterior_id` do último `os_historico` que levou ao status atual; **valida que esse status ainda existe** (senão fallback 1º `em_andamento` → 1º `aberta`); limpa `data_conclusao`/`data_entrega`. Botão só aparece quando `tipo='entregue'`.

---

## 6. GARANTIA — sem status dedicado

Identificada por **`tipo_servico='garantia'` + `os_origem_id`** + selo calculado.

- **Abrir retorno (`registrarGarantia`):** só se `garantia_ate >= hoje`. Cria NOVA OS: `tipo_servico='garantia'`, `os_origem_id`, `valor_total=0`, `pago`; status inicial = 1º `tipo='em_andamento'` ("Em análise").
- **Finalizar (`finalizarGarantia`):** garantia finalizada = aparelho já devolvido → move para `codigo='fechado'` (**tipo entregue**; fallback pronto/concluida), carimba `data_conclusao` **e** `data_entrega`, `valor_total=0`, `pago`, nova `garantia_ate`.
- **Selo "Garantia finalizada"** (view): usa o **flag explícito `ordens_servico.garantia_finalizada`** (setado SÓ pelo botão Finalizar). Não é mais inferido do status — mover a OS pra Pronto na mão não liga o selo.
- **Detecção "em garantia":** `tipo_servico='garantia'` e `os_origem_id IS NOT NULL`.

### Regra de contagem (crucial)
Um retorno de garantia está em **UM** de dois lugares, nunca nos dois:
- **Botão/filtro "Em Garantia"** = garantia **ATIVA** = `tipo_servico='garantia'` e `os_origem_id NOT NULL` e status `tipo NOT IN ('concluida','entregue','cancelada')`.
- **Chip do status** (Pronto/Fechado/Sem Conserto) = garantia **terminada** = status `tipo IN ('concluida','entregue','cancelada')`.

**Fonte ÚNICA da regra de contagem:** `Models/OrdemServico` (`totalEmGarantia` + `totaisPorStatus`). A tela `/os` e a **sidebar** (`layouts/main.php`) ambas **chamam esses métodos** (a sidebar não tem mais query própria — centralizado). Também há a busca `buscarFechadas` (`tipo IN ('concluida','entregue')`).

---

## 7. Invariantes que o sistema ASSUME

1. Existe ≥1 status `tipo='concluida'` (Pronto) e ≥1 `tipo='entregue'` (Fechado) por empresa (garantido pelos nativos travados).
2. `fechar()` mira `codigo='fechado'`; `finalizarGarantia` mira `codigo='fechado'`; ambos com fallback por `tipo`.
3. Receita = `tipo IN ('concluida','entregue')` + `valor_total>0`.
4. `status_id` é FK RESTRICT.

---

## 8. Pontos para AUDITORIA (perguntas abertas — encontre os furos)

Sem respostas de propósito. Avalie cada um e diga se é bug, risco ou está ok:

1. Um status **personalizado** pode ter `tipo='concluida'` ou `'entregue'`, e a empresa pode **reordená-lo** (arrastar) para ordem menor que os nativos. Isso afeta roteamento de `fechar`/`finalizarGarantia`? E a **receita**? E os chips?
2. ~~Regra de contagem em 2 lugares~~ **(RESOLVIDO 2026-07-13: centralizada no Model; sidebar chama o método.)**
3. Um retorno de garantia movido para **`cancelada`** (Sem Conserto): cai em qual contagem? Faz sentido?
4. `reabrir()` depende de `os_historico`, que **não tem FK** para `os_status`. O que acontece se o status anterior foi excluído? E se o histórico estiver ausente/corrompido?
5. A validação de `sem_valor` acontece em `atualizarStatus`. E nas **outras portas** que trocam status direto (`fechar`, `reabrir`, `finalizarGarantia`)? Há caminho para uma OS com valor cair num status `sem_valor=1`? (lembre: os nativos têm `sem_valor` **editável** pela empresa.)
6. **Multi-tenant:** toda query que lê/conta/atualiza OS e status filtra por `empresa_id`? Procure qualquer `JOIN`/subquery que cruze empresas (ex.: `os_status` sem `empresa_id` na condição).
7. `data_conclusao` vs `data_entrega`: em quais transições cada uma é (ou deveria ser) carimbada? Há status que gera receita mas nunca carimba data? A reabertura limpa as duas?
8. `situacao_pagamento` e `valor_total` são coerentes ao mudar de status? Ex.: fechar com desconto/taxa de cartão — o valor que vira receita é o líquido ou o bruto?
9. Concorrência: dois usuários mudando a mesma OS ao mesmo tempo; reordenar status enquanto se abre uma OS. Alguma corrida?
10. ~~Selo "Garantia finalizada" inferido do status~~ **(RESOLVIDO 2026-07-13: agora usa o flag explícito `garantia_finalizada`, setado só pelo botão Finalizar; mover pra Pronto na mão não liga o selo.)**
