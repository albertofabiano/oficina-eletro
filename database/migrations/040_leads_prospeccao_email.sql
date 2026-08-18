-- Disparo de e-mail de prospecção (convite pro diretório grátis) — ver CLAUDE.md
-- "Disparo de e-mail de prospecção" e MasterController::prospeccaoDisparar().
ALTER TABLE `leads_prospeccao`
  ADD COLUMN IF NOT EXISTS `email_convite_enviado_em` DATETIME NULL,
  ADD COLUMN IF NOT EXISTS `email_unsub_token` VARCHAR(40) NULL;

ALTER TABLE `leads_prospeccao`
  ADD INDEX IF NOT EXISTS `idx_leads_municipio` (`municipio`),
  ADD INDEX IF NOT EXISTS `idx_leads_unsub_token` (`email_unsub_token`);
