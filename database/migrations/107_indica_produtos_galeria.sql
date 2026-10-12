-- Galeria de fotos do produto do Fixa Indica — `imagem_url` continua sendo a capa (1ª foto),
-- `imagens_extra` guarda as demais (até 7, 8 no total com a capa) pra exibir em carrossel na
-- página pública. Mesmo tipo JSON nativo já usado em `selos` nesta tabela.
ALTER TABLE `indica_produtos`
  ADD COLUMN `imagens_extra` JSON NULL AFTER `imagem_url`;
