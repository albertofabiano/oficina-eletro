-- Galeria de fotos do produto — pedido do usuário: cadastro de produto passa a aceitar até 4
-- fotos no total (1 capa + 3 de galeria), mesmo padrão já validado no Marketplace
-- (`marketplace_anuncios.imagem_principal`/`imagens_galeria`, ver migration
-- 024_documenta_diretorio_marketplace.sql) — reaproveitado aqui em vez de inventar um
-- formato novo. `produtos.imagem` já existe desde o schema original e continua sendo a capa;
-- só a galeria é coluna nova.

ALTER TABLE `produtos`
  ADD COLUMN IF NOT EXISTS `imagens_galeria` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL
    CHECK (JSON_VALID(`imagens_galeria`));
