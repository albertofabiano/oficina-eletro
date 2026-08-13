-- Catálogo de serviços padronizados por empresa — alimenta o autocomplete do modal
-- "Novo Serviço" da OS (descrição livre continua permitida, isso é só sugestão/atalho).
CREATE TABLE IF NOT EXISTS `servicos_catalogo` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `descricao` VARCHAR(150) NOT NULL,
  `valor_padrao` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  KEY `idx_servicos_catalogo_busca` (`empresa_id`, `ativo`, `descricao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
