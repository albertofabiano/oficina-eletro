-- Vínculo estruturado entre a venda do PDV e o que ela gera no Financeiro/Estoque — faltava.
-- Achado investigando um relato de cliente: ele corrigiu a forma de pagamento de uma venda
-- (errou "Dinheiro" em vez de "Pix da maquininha") excluindo o lançamento de receita direto
-- no Fluxo de Caixa e relançando a venda certa — só que isso nunca existiu como fluxo
-- oficial (não havia exclusão de venda no PDV nenhuma) e o estoque do item vendido ficou
-- descontado duas vezes, porque não existia jeito nenhum de achar e desfazer tudo que a
-- venda original tinha gerado.
--
-- fin_lancamentos.venda_id segue o mesmo padrão já usado pra os_id/agenda_id (migrations
-- 001/033): junta TODAS as linhas que uma venda gera (receita por forma de pagamento/parcela
-- + despesa de taxa de cartão), não só a primeira — que era tudo que pdv_vendas.lancamento_id
-- guardava até aqui (parcelas e taxa ficavam sem nenhum vínculo).
-- movimentos_estoque.venda_id é o equivalente pro lado do estoque — hoje só tinha "os_id"
-- (sempre NULL pra venda de PDV) e um texto livre em "motivo", frágil demais pra reverter de
-- forma confiável.
--
-- Sem FOREIGN KEY pra pdv_vendas de propósito: assim como os_pagamentos/cobrancas
-- (documentado no CLAUDE.md), pdv_vendas existe em produção sem migration commitada — não dá
-- pra garantir o tipo exato da coluna id sem rodar DESCRIBE no VPS antes. movimentos_estoque
-- já tem outro precedente do mesmo tipo (compra_id, também sem FK) — mesma cautela aqui.

ALTER TABLE `fin_lancamentos`
  ADD COLUMN IF NOT EXISTS `venda_id` INT UNSIGNED DEFAULT NULL AFTER `os_id`,
  ADD INDEX IF NOT EXISTS `idx_lanc_venda` (`venda_id`);

ALTER TABLE `movimentos_estoque`
  ADD COLUMN IF NOT EXISTS `venda_id` INT UNSIGNED DEFAULT NULL AFTER `os_id`,
  ADD INDEX IF NOT EXISTS `idx_mov_venda` (`venda_id`);
