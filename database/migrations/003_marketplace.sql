-- =====================================================
-- MARKETPLACE DE PEÇAS ENTRE ASSISTÊNCIAS
-- Módulo de créditos e anúncios globais
-- =====================================================

-- Saldo de créditos por empresa
CREATE TABLE IF NOT EXISTS `marketplace_creditos` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id`      INT UNSIGNED NOT NULL,
  `saldo_creditos`  INT UNSIGNED NOT NULL DEFAULT 0,
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_empresa` (`empresa_id`),
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Anúncios globais (leitura por todas as empresas)
CREATE TABLE IF NOT EXISTS `marketplace_anuncios` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id_vendedor` INT UNSIGNED NOT NULL,
  `titulo`              VARCHAR(120) NOT NULL,
  `descricao`           TEXT,
  `tipo`                VARCHAR(60),
  `marca`               VARCHAR(60),
  `modelo`              VARCHAR(100),
  `codigo_interno`      VARCHAR(60),
  `valor`               DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status`              ENUM('ativo','pausado','vendido') DEFAULT 'ativo',
  `data_criacao`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`empresa_id_vendedor`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  INDEX `idx_status` (`status`),
  INDEX `idx_vendedor` (`empresa_id_vendedor`),
  INDEX `idx_tipo` (`tipo`),
  INDEX `idx_marca` (`marca`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Histórico de movimentações de créditos
CREATE TABLE IF NOT EXISTS `marketplace_historico_creditos` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id`   INT UNSIGNED NOT NULL,
  `tipo`         ENUM('consumo','compra','ajuste') NOT NULL,
  `quantidade`   INT NOT NULL,
  `justificativa` VARCHAR(255),
  `anuncio_id`   INT UNSIGNED DEFAULT NULL,
  `usuario_id`   INT UNSIGNED DEFAULT NULL,
  `data`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`anuncio_id`) REFERENCES `marketplace_anuncios`(`id`) ON DELETE SET NULL,
  INDEX `idx_empresa_hist` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Criar saldo inicial zerado para empresas existentes
INSERT IGNORE INTO `marketplace_creditos` (`empresa_id`, `saldo_creditos`)
SELECT `id`, 0 FROM `empresas` WHERE `ativo` = 1;
