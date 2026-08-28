-- Descadastro do convite de "reivindique seu perfil" (ver CLAUDE.md "Disparo de e-mail pra
-- captação de clientes do diretório" e App\Services\Prospeccao\DisparoDiretorioService) —
-- diretorio_leads_email não tem coluna de status tipo leads_prospeccao, então o descadastro é
-- só esse carimbo: presente = nunca mais entra num disparo futuro.
ALTER TABLE `diretorio_leads_email`
  ADD COLUMN IF NOT EXISTS `descadastrado_em` DATETIME NULL;
