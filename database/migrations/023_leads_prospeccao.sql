-- Leads frios pra convidar pro FixaOS, importados de dados abertos de CNPJ
-- (Receita Federal), filtrados pelas CNAEs de assistência técnica / venda de
-- componentes eletrônicos. Não tem relação com o diretório público
-- (diretorio_avaliacoes/listagem_publica) — isso aqui é só painel interno do
-- /master pra prospecção manual, sem exposição pública nem envio automático.
CREATE TABLE IF NOT EXISTS `leads_prospeccao` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `cnpj` VARCHAR(14) NOT NULL,
  `razao_social` VARCHAR(255) NOT NULL,
  `nome_fantasia` VARCHAR(255) NULL,
  `telefone` VARCHAR(20) NULL,
  `email` VARCHAR(150) NULL,
  `cnae` VARCHAR(10) NOT NULL,
  `municipio` VARCHAR(120) NULL,
  `uf` CHAR(2) NULL,
  `situacao_cadastral` VARCHAR(30) NULL,
  `status` ENUM('novo','contatado','convertido','descartado') NOT NULL DEFAULT 'novo',
  `observacoes` TEXT NULL,
  `contatado_em` TIMESTAMP NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_cnpj` (`cnpj`),
  KEY `idx_status` (`status`),
  KEY `idx_cnae` (`cnae`),
  KEY `idx_uf` (`uf`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
