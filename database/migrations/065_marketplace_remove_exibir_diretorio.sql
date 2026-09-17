-- Limpeza: `exibir_diretorio` (criada em 063) ficou sem uso — a vitrine do Diretório foi
-- desvencilhada do Marketplace e passou a viver em `diretorio_produtos` (ver 064), tabela e
-- fluxo de cadastro próprios, sem depender de anúncio/crédito do Marketplace.
ALTER TABLE marketplace_anuncios
  DROP COLUMN IF EXISTS exibir_diretorio;
