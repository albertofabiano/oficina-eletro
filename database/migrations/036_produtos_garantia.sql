-- Garantia do produto (em dias) — obrigatória no cadastro/edição de produto (validado no
-- ProdutoController), soma automaticamente à descrição do item quando vendido pelo PDV.
-- Default 90 pros produtos já existentes (mesmo padrão de garantia_dias já usado em
-- ordens_servico) — dá pra editar depois; 0 é um valor válido explícito ("sem garantia").
ALTER TABLE `produtos`
  ADD COLUMN `garantia_dias` SMALLINT UNSIGNED NOT NULL DEFAULT 90 AFTER `valor_venda`;
