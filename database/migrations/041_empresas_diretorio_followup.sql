-- Acompanhamento pós-publicação do perfil grátis do diretório — ver CLAUDE.md
-- "Acompanhamento pós-cadastro no diretório" e scripts/disparar_followup_diretorio.php.
ALTER TABLE `empresas`
  ADD COLUMN IF NOT EXISTS `diretorio_publicado_em` DATETIME NULL,
  ADD COLUMN IF NOT EXISTS `diretorio_followup_enviado_em` DATETIME NULL;
