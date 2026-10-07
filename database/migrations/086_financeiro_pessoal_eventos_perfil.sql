-- Fixa Fase 1 — `financeiro_pessoal_eventos` ganha `perfil_id` (escopo, igual todo o resto do
-- módulo) e `lancamento_id` (nulo) — vínculo OPCIONAL a um lançamento específico, pra quando o
-- usuário quer um lembrete PRÓPRIO (ex.: horário diferente do vencimento, ou uma nota extra)
-- além do que a Agenda já mostra sozinha pra todo lançamento em aberto.
--
-- Importante: a Agenda passa a ler `financeiro_pessoal_lancamentos` DIRETO pra mostrar
-- vencimento (ver FinanceiroPessoalController::calendario()) — isso NUNCA grava uma linha nova
-- em `financeiro_pessoal_eventos`. `lancamento_id` não é populado automaticamente por nenhum
-- código; só existe pra quando o próprio usuário cria um evento manual e escolhe vinculá-lo a
-- um lançamento (ação explícita, não implementada nesta rodada da Fase 1 — o campo só existe no
-- schema, groundwork).
ALTER TABLE `financeiro_pessoal_eventos`
  ADD COLUMN `perfil_id`      INT UNSIGNED NULL AFTER `usuario_id`,
  ADD COLUMN `lancamento_id`  INT UNSIGNED NULL AFTER `perfil_id`;

ALTER TABLE `financeiro_pessoal_eventos`
  ADD KEY `idx_perfil` (`perfil_id`),
  ADD CONSTRAINT `fk_fpevt_perfil` FOREIGN KEY (`perfil_id`) REFERENCES `financeiro_pessoal_perfis`(`id`) ON DELETE SET NULL;

ALTER TABLE `financeiro_pessoal_eventos`
  ADD KEY `idx_lancamento` (`lancamento_id`),
  ADD CONSTRAINT `fk_fpevt_lancamento` FOREIGN KEY (`lancamento_id`) REFERENCES `financeiro_pessoal_lancamentos`(`id`) ON DELETE SET NULL;
