-- Liga um anúncio do marketplace ao produto de origem (Estoque), quando criado
-- a partir do botão "Anunciar no Diretório" na tela de Produtos.
-- Permite reaproveitar dados do produto e exibir a quantidade em estoque no anúncio.
ALTER TABLE `marketplace_anuncios`
  ADD COLUMN `produto_id` INT UNSIGNED NULL AFTER `empresa_id_vendedor`,
  ADD CONSTRAINT `fk_marketplace_anuncio_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE SET NULL;
