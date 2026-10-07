-- Fixa Fase 1 — `financeiro_pessoal_lancamentos` ganha perfil/conta + os campos novos da spec.
--
-- `perfil_id`/`conta_id` nascem NULL (mesmo motivo de 084: backfill é
-- scripts/migrar_fixa_perfis.php, não dá pra popular direito num ALTER puro). `usuario_id`
-- continua existindo — "toda consulta filtra por usuario_id e perfil_id" (pedido explícito),
-- então as duas colunas convivem, não uma substitui a outra.
--
-- `tipo` ganha 'transferencia' (entre contas do mesmo perfil — ex.: saque de dinheiro, ou
-- mover de poupança pra corrente) — mesmo padrão já usado em `agenda.tipo` (ampliar ENUM sem
-- precisar remapear valor antigo, só ADD/MODIFY direto).
--
-- `data_competencia` é o mês/ano a que o gasto/receita realmente pertence (ex.: boleto de
-- dezembro pago em janeiro ainda é despesa de dezembro na competência) — opcional, pensado pra
-- DRE de perfil PJ (groundwork desta fase, sem relatório nenhum consumindo isso ainda).
ALTER TABLE `financeiro_pessoal_lancamentos`
  ADD COLUMN `perfil_id`        INT UNSIGNED NULL AFTER `usuario_id`,
  ADD COLUMN `conta_id`         INT UNSIGNED NULL AFTER `perfil_id`,
  MODIFY COLUMN `tipo`          ENUM('receita','despesa','transferencia') NOT NULL DEFAULT 'despesa',
  ADD COLUMN `data_competencia` DATE NULL AFTER `pago_em`,
  ADD COLUMN `observacao`       VARCHAR(500) NULL AFTER `data_competencia`,
  ADD COLUMN `anexo_url`        VARCHAR(255) NULL AFTER `observacao`,
  ADD COLUMN `codigo_barras`    VARCHAR(80) NULL AFTER `anexo_url`,
  ADD COLUMN `pix_copia_cola`   VARCHAR(255) NULL AFTER `codigo_barras`,
  -- DEFAULT 1 pra toda linha já existente (lançamento manual sempre gravou a hora real de
  -- quando foi digitado, ver data_hora) — só passa a nascer 0 nos pontos novos que gravam
  -- só uma data sem hora de verdade (conta escaneada, lançamento futuro agendado por data).
  ADD COLUMN `hora_informada`   TINYINT(1) NOT NULL DEFAULT 1 AFTER `pix_copia_cola`;

-- FKs em statements separados (ver nota em 084) — cada um falha sozinho num rerun, sem travar
-- os outros.
ALTER TABLE `financeiro_pessoal_lancamentos`
  ADD KEY `idx_perfil` (`perfil_id`),
  ADD CONSTRAINT `fk_fplanc_perfil` FOREIGN KEY (`perfil_id`) REFERENCES `financeiro_pessoal_perfis`(`id`) ON DELETE SET NULL;

ALTER TABLE `financeiro_pessoal_lancamentos`
  ADD KEY `idx_conta` (`conta_id`),
  ADD CONSTRAINT `fk_fplanc_conta` FOREIGN KEY (`conta_id`) REFERENCES `financeiro_pessoal_contas`(`id`) ON DELETE SET NULL;
