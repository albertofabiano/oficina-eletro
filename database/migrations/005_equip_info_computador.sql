-- Campos extras de equipamento para notebooks, PCs, placas de vídeo etc.
ALTER TABLE `equipamentos`
  ADD COLUMN `tipo_armazenamento` VARCHAR(60)  NULL AFTER `senha_desbloqueio`,
  ADD COLUMN `memoria_ram`        VARCHAR(40)  NULL AFTER `tipo_armazenamento`,
  ADD COLUMN `placa_video`        VARCHAR(100) NULL AFTER `memoria_ram`,
  ADD COLUMN `placa_mae`          VARCHAR(100) NULL AFTER `placa_video`,
  ADD COLUMN `processador`        VARCHAR(100) NULL AFTER `placa_mae`;
