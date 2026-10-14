-- Fixa Indica ganha uma segunda loja: AliExpress, ao lado do Mercado Livre que já existia
-- desde a Fase 1. `loja` decide qual badge/cor o produto mostra no site público e qual rótulo
-- o botão de compra usa ("Ver no Mercado Livre" vs "Ver no AliExpress") — não decide nada de
-- preço/disponibilidade, que continuam sendo os mesmos campos de sempre (preenchidos manual,
-- ou via "Buscar dados do anúncio" só pra Mercado Livre — AliExpress não tem integração de API,
-- é sempre cadastro manual).
ALTER TABLE `indica_produtos`
  ADD COLUMN IF NOT EXISTS `loja` ENUM('mercado_livre','aliexpress') NOT NULL DEFAULT 'mercado_livre' AFTER `categoria_id`;
