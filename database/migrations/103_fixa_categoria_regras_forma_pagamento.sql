-- Carteira Fixa — `financeiro_pessoal_categoria_regras` (aprendizado por beneficiário) ganha
-- `forma_pagamento_id`, mesmo padrão exato de `conta_id` (migration 099): sobrescrita sempre
-- pela ÚLTIMA forma usada pra aquele beneficiário, sem contador de confiança dedicado (só a
-- categoria tem `usos`/`confirmada`) — "conta de luz tende a ser sempre paga do mesmo jeito"
-- (ROTEIRO-carteira-fixa.md §7).
ALTER TABLE `financeiro_pessoal_categoria_regras`
  ADD COLUMN IF NOT EXISTS `forma_pagamento_id` INT UNSIGNED NULL AFTER `conta_id`;

ALTER TABLE `financeiro_pessoal_categoria_regras`
  ADD KEY `idx_forma_pagamento` (`forma_pagamento_id`),
  ADD CONSTRAINT `fk_fpcr_forma_pagamento` FOREIGN KEY (`forma_pagamento_id`) REFERENCES `financeiro_pessoal_formas_pagamento`(`id`) ON DELETE SET NULL;
