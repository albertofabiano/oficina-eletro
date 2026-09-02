-- Migration retroativa: `os_pagamentos` já existia em produção, gravada desde o fechamento de
-- OS via cartão (ver OrdemServicoController::fechar()), mas nunca teve CREATE TABLE commitado
-- no histórico de migrations — mesmo gap já documentado em CLAUDE.md ("Financeiro: taxa de
-- cartão faltando no atalho 'Receber OS'"). Um checkout novo do zero (sandbox, disaster
-- recovery) não subia essa tabela.
--
-- Schema confirmado via `DESCRIBE os_pagamentos;` direto em produção (2026-09-02) — não é
-- reconstrução por inferência do código, é cópia fiel do schema real. CREATE TABLE
-- IF NOT EXISTS: rodar isso contra produção (onde a tabela já existe) é inofensivo; só
-- materializa a tabela de fato em ambientes novos.
--
-- Guarda uma linha por forma de pagamento de uma OS fechada via cartão (taxa calculada,
-- valor líquido vs. cobrado do cliente) — é o guard de idempotência de fechar(): já existir
-- linha aqui pra aquela OS é o que impede lançar a receita duas vezes.

CREATE TABLE IF NOT EXISTS `os_pagamentos` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `os_id` INT UNSIGNED NOT NULL,
  `forma_pagamento` VARCHAR(50) NOT NULL,
  `valor` DECIMAL(12,2) NOT NULL,
  `parcelas` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `taxa_percentual` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `taxa_valor` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `valor_cobrado` DECIMAL(12,2) NOT NULL,
  `criado_em` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_os_pagamentos_os` (`os_id`),
  KEY `idx_os_pagamentos_empresa` (`empresa_id`),
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`os_id`) REFERENCES `ordens_servico`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
