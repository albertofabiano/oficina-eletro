-- Tabela dedicada com os e-mails extraídos das empresas do Diretório (ver CLAUDE.md
-- "Extração de e-mails do Diretório pra captação de clientes" e
-- scripts/extrair_emails_diretorio.php) — base própria pra outreach, sem misturar com
-- leads_prospeccao (base bruta de CNPJ, algumas dessas empresas já viraram ficha real do
-- diretório e merecem uma mensagem diferente: "reivindique seu perfil", não "cadastre-se").
CREATE TABLE IF NOT EXISTS `diretorio_leads_email` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `nome_fantasia` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `telefone` VARCHAR(20) NULL,
  `cidade` VARCHAR(80) NULL,
  `uf` CHAR(2) NULL,
  `cnpj` VARCHAR(18) NULL,
  `reivindicada` TINYINT(1) NOT NULL DEFAULT 0,
  `email_convite_enviado_em` DATETIME NULL,
  `email_unsub_token` VARCHAR(40) NULL,
  `email_aberto_em` DATETIME NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_empresa_id` (`empresa_id`),
  KEY `idx_email` (`email`),
  KEY `idx_uf` (`uf`),
  KEY `idx_unsub_token` (`email_unsub_token`),
  CONSTRAINT `fk_diretorio_leads_email_empresa` FOREIGN KEY (`empresa_id`)
    REFERENCES `empresas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
