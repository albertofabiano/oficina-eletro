-- Carteira Fixa — Contas recorrentes (financeiro_pessoal_recorrentes) ganham forma de pagamento
-- opcional, mesmo conceito das Fases 1-5 (ROTEIRO-carteira-fixa.md). Útil pro caso real "a
-- assinatura é cobrada no cartão todo dia 5, mas o dinheiro só sai de verdade da conta corrente
-- no vencimento da fatura" — `dia_vencimento` continua sendo o dia em que a cobrança "acontece"
-- (vira `data_evento` do lançamento gerado, ver RecorrenteService::gerarPendentes()), a forma
-- decide a data EFETIVA (gravada em `vencimento`), igual já faz pro lançamento manual/scanner.
--
-- NULL = comportamento de sempre (vencimento = exatamente o dia do mês configurado, sem nenhuma
-- indireção) — toda recorrência já cadastrada antes desta coluna continua idêntica.
ALTER TABLE `financeiro_pessoal_recorrentes`
  ADD COLUMN IF NOT EXISTS `forma_pagamento_id` INT UNSIGNED NULL AFTER `conta_id`;

ALTER TABLE `financeiro_pessoal_recorrentes`
  ADD KEY `idx_forma_pagamento` (`forma_pagamento_id`),
  ADD CONSTRAINT `fk_fprec_forma_pagamento` FOREIGN KEY (`forma_pagamento_id`) REFERENCES `financeiro_pessoal_formas_pagamento`(`id`) ON DELETE SET NULL;
