-- Vencimento e data de pagamento por lançamento (pedido do usuário: "falta vencimento e o
-- dia que foi pago") — os dois opcionais, confirmado com o usuário: campo pra um gasto que
-- já é cadastrado antes de pagar (ex.: conta de luz que vence dia 10, cadastrada antes, marcada
-- como paga depois). Deliberadamente NÃO reaproveita `financeiro_pessoal_itens`
-- (vencimento/pago_em) — essa tabela é da antiga "Contas e débitos" (listas/itens), já
-- removida da UI de propósito (ver CLAUDE.md) e sem view nenhuma hoje; os campos novos ficam
-- direto no lançamento, única tela do módulo que de fato existe pra isso.

ALTER TABLE `financeiro_pessoal_lancamentos`
  ADD COLUMN `vencimento` DATE NULL AFTER `data_hora`,
  ADD COLUMN `pago_em` DATE NULL AFTER `vencimento`;
