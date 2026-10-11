-- Carteira Fixa — `financeiro_pessoal_lancamentos` ganha os 3 campos da forma de pagamento:
--
-- `forma_pagamento_id` — qual forma (financeiro_pessoal_formas_pagamento) decidiu a data
-- efetiva deste lançamento. NULL pra todo lançamento que não passou pelo seletor novo (todo
-- lançamento manual feito antes desta feature, e qualquer um feito depois sem escolher forma —
-- o campo é opcional, o fluxo antigo de digitar vencimento/pago_em direto continua existindo).
--
-- `data_evento` — a data do evento no mundo real ANTES da forma mexer nela (data da compra, do
-- boleto impresso, da venda) — puramente informativo, pra exibir ("comprado em 10/out"). Nenhum
-- cálculo de saldo olha pra essa coluna, igual a `data_competencia` (085), que também é só
-- groundwork.
--
-- `compra_id` — agrupa as parcelas de uma mesma compra parcelada (1 lançamento por parcela,
-- valor rateado por App\Services\Fixa\FormaPagamentoService::ratear()). A 1ª parcela recebe o
-- próprio `id` aqui (self-reference sem FK — grava depois do INSERT, quando o id já existe);
-- as demais recebem o mesmo valor. Sem FK de propósito: cada parcela continua editável/
-- excluível individualmente, como qualquer lançamento hoje — `compra_id` é só rótulo de
-- agrupamento pra exibição (ex. "2/3"), não uma relação que precise cascatear nada.
ALTER TABLE `financeiro_pessoal_lancamentos`
  ADD COLUMN IF NOT EXISTS `forma_pagamento_id` INT UNSIGNED NULL AFTER `conta_id`,
  ADD COLUMN IF NOT EXISTS `data_evento`        DATE NULL AFTER `data_competencia`,
  ADD COLUMN IF NOT EXISTS `compra_id`          INT UNSIGNED NULL AFTER `recorrente_id`;

ALTER TABLE `financeiro_pessoal_lancamentos`
  ADD KEY `idx_forma_pagamento` (`forma_pagamento_id`),
  ADD CONSTRAINT `fk_fplanc_forma_pagamento` FOREIGN KEY (`forma_pagamento_id`) REFERENCES `financeiro_pessoal_formas_pagamento`(`id`) ON DELETE SET NULL;

ALTER TABLE `financeiro_pessoal_lancamentos`
  ADD KEY `idx_compra` (`compra_id`);
