-- Rastreamento de abertura do convite de prospecção (pixel de 1x1) — ver
-- MasterController::prospeccaoPixel() e CLAUDE.md "Rastreamento de abertura do e-mail".
ALTER TABLE `leads_prospeccao`
  ADD COLUMN IF NOT EXISTS `email_aberto_em` DATETIME NULL;
