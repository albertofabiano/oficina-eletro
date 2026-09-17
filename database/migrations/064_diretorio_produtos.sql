-- Vitrine de produtos do Diretório, desvencilhada do Marketplace de Peças — tabela própria,
-- sem depender de crédito/anúncio do Marketplace. Benefício de plano pago ativo (mesmo
-- critério de perfil_diretorio_completo()), até 10 produtos por vez, cada um com capa + até
-- 2 fotos de galeria (3 no total), padronizadas em WebP 800x800 fundo branco (mesmo pipeline
-- de ImageService::padronizar() já usado noutros cadastros de foto do sistema).
CREATE TABLE IF NOT EXISTS `diretorio_produtos` (
  `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id`        INT UNSIGNED NOT NULL,
  `produto_id`        INT UNSIGNED NULL,
  `titulo`            VARCHAR(120) NOT NULL,
  `descricao`         TEXT,
  `valor`             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `imagem_principal`  VARCHAR(150) NULL,
  `imagens_galeria`   TEXT NULL,
  `status`            ENUM('ativo','vendido') NOT NULL DEFAULT 'ativo',
  `criado_em`         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE SET NULL,
  INDEX `idx_empresa` (`empresa_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
