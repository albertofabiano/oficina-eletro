-- Financeiro pessoal — estende a tabela de aprendizado de categoria (077_financeiro_pessoal_
-- categoria_regras.sql) pra cobrir os 3 jeitos de lançar (manual, scanner, voz — ver pedido do
-- usuário) em vez de só o scanner de conta. Ganha: isolamento por PERFIL (antes era só por
-- usuario_id — dois perfis do mesmo usuário compartilhavam a mesma regra, o que não faz
-- sentido já que cada perfil tem seu próprio catálogo de categoria/conta), sugestão de CONTA
-- (além de categoria), e um contador de confiança (`usos`/`confirmada`) em vez de "1 correção
-- já é definitivo pra sempre".
--
-- `perfil_id` nasce NULL (mesmo motivo de 082/085: não dá pra popular direito num ALTER puro,
-- backfill fica pra um script à parte se um dia fizer falta — tabela pequena, de cache de
-- sugestão, não dado financeiro de verdade) — linha antiga sem perfil_id continua existindo,
-- só não compete pela mesma UNIQUE de uma linha nova com perfil_id preenchido (MySQL trata NULL
-- como "não igual a nada" em UNIQUE, então não há colisão nem erro no ALTER).
ALTER TABLE `financeiro_pessoal_categoria_regras`
  ADD COLUMN `perfil_id`  INT UNSIGNED NULL AFTER `usuario_id`,
  ADD COLUMN `conta_id`   INT UNSIGNED NULL AFTER `categoria`,
  ADD COLUMN `usos`       INT UNSIGNED NOT NULL DEFAULT 1 AFTER `conta_id`,
  ADD COLUMN `confirmada` TINYINT(1) NOT NULL DEFAULT 0 AFTER `usos`;

ALTER TABLE `financeiro_pessoal_categoria_regras` DROP INDEX `uq_usuario_benef`;
ALTER TABLE `financeiro_pessoal_categoria_regras`
  ADD UNIQUE KEY `uq_usuario_perfil_benef` (`usuario_id`, `perfil_id`, `beneficiario_normalizado`);

-- FKs em statements separados (mesma nota de 084/085: cada um falha sozinho num rerun, sem
-- travar os outros).
ALTER TABLE `financeiro_pessoal_categoria_regras`
  ADD KEY `idx_perfil` (`perfil_id`),
  ADD CONSTRAINT `fk_fpcr_perfil` FOREIGN KEY (`perfil_id`) REFERENCES `financeiro_pessoal_perfis`(`id`) ON DELETE CASCADE;

ALTER TABLE `financeiro_pessoal_categoria_regras`
  ADD KEY `idx_conta` (`conta_id`),
  ADD CONSTRAINT `fk_fpcr_conta` FOREIGN KEY (`conta_id`) REFERENCES `financeiro_pessoal_contas`(`id`) ON DELETE SET NULL;
