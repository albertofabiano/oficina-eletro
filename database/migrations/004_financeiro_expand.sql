-- =====================================================
-- EXPANSÃO DO MÓDULO FINANCEIRO
-- Adiciona status em fin_categorias
-- =====================================================

ALTER TABLE `fin_categorias`
  ADD COLUMN IF NOT EXISTS `status` ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo' AFTER `nome`;

ALTER TABLE `fin_lancamentos`
  ADD COLUMN IF NOT EXISTS `conta_simples` ENUM('caixa','banco','cartao') DEFAULT 'caixa' AFTER `conta_id`;

-- Seeds: categorias padrão se não existirem
INSERT IGNORE INTO `fin_categorias` (`empresa_id`, `tipo`, `nome`, `status`)
SELECT e.id, 'receita', 'Serviços de Reparo', 'ativo' FROM empresas e WHERE e.ativo = 1
ON DUPLICATE KEY UPDATE nome = nome;
