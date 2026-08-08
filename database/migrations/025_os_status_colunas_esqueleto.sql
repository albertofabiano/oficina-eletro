-- Garante as colunas que o código já espera em os_status (codigo/cor_fonte/
-- permite_fechar/sem_valor/bloqueado). Em produção elas já existem (foram
-- adicionadas fora do controle de migrations neste projeto — ver CLAUDE.md,
-- "Pendências"); isso aqui é defensivo, pra qualquer ambiente que só tenha
-- rodado 001_schema.sql em diante ficar igual. Pré-requisito da 026, que
-- insere o novo status nativo "Laudo Técnico".
ALTER TABLE `os_status`
  ADD COLUMN IF NOT EXISTS `codigo` VARCHAR(50) NULL AFTER `empresa_id`,
  ADD COLUMN IF NOT EXISTS `cor_fonte` VARCHAR(7) DEFAULT '#ffffff' AFTER `cor`,
  ADD COLUMN IF NOT EXISTS `permite_fechar` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `sem_valor` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `bloqueado` TINYINT(1) NOT NULL DEFAULT 0;
