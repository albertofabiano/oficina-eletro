-- Horário de funcionamento exibido no perfil público do diretório.
ALTER TABLE `empresas`
  ADD COLUMN `horario_funcionamento` TEXT NULL;
