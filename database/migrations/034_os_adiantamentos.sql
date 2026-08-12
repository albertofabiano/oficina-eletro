-- Adiantamento de OS: pedido do usuário — quando a peça é cara, a assistência pede um valor
-- adiantado ao cliente antes de comprar/consertar. Cada registro já vira receita de verdade no
-- Financeiro na hora (mesmas regras de forma de pagamento/taxa de cartão/repasse do fechamento
-- da OS, ver OrdemServicoController::fechar()), sem esperar a OS fechar.
--
-- fin_lancamento_id/fin_lancamento_taxa_id guardam a receita e (se houver) a despesa da taxa
-- gerados por este adiantamento, pra excluirAdiantamento() conseguir estornar os dois de volta
-- se o registro for removido por engano. ON DELETE SET NULL (não CASCADE): apagar o lançamento
-- direto na tela do Financeiro não deve apagar o histórico do adiantamento, só perder o vínculo.

CREATE TABLE IF NOT EXISTS `os_adiantamentos` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `os_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED DEFAULT NULL,
  `forma_pagamento` VARCHAR(20) NOT NULL,
  `parcelas` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `valor` DECIMAL(10,2) NOT NULL,
  `taxa_percentual` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `taxa_valor` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `valor_cobrado` DECIMAL(10,2) NOT NULL,
  `fin_lancamento_id` INT UNSIGNED DEFAULT NULL,
  `fin_lancamento_taxa_id` INT UNSIGNED DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`os_id`) REFERENCES `ordens_servico`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`fin_lancamento_id`) REFERENCES `fin_lancamentos`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`fin_lancamento_taxa_id`) REFERENCES `fin_lancamentos`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
