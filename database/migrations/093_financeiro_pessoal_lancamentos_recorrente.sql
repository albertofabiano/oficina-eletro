-- `financeiro_pessoal_lancamentos.recorrente_id` — de qual molde (financeiro_pessoal_recorrentes)
-- um lançamento gerado automaticamente veio. NULL pra todo lançamento digitado à mão ou lido
-- por foto, como sempre foi; só populado por RecorrenteService::gerarPendentes(). É o que torna
-- a geração idempotente (não existe outro jeito de saber "já gerei o aluguel de março?" sem
-- essa referência) e o que permite um dia mostrar "gerado pela conta recorrente X" na lista.
ALTER TABLE `financeiro_pessoal_lancamentos`
  ADD COLUMN `recorrente_id` INT UNSIGNED NULL AFTER `perfil_id`;

ALTER TABLE `financeiro_pessoal_lancamentos`
  ADD KEY `idx_recorrente` (`recorrente_id`),
  ADD CONSTRAINT `fk_fplanc_recorrente` FOREIGN KEY (`recorrente_id`) REFERENCES `financeiro_pessoal_recorrentes`(`id`) ON DELETE SET NULL;
