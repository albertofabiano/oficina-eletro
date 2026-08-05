-- Migration retroativa (documentação de schema real de produção).
--
-- Estas colunas/tabelas já existem em produção há tempo (módulo de Diretório
-- Público + Marketplace de peças) mas nunca tinham sido commitadas em nenhuma
-- migration — mesma classe de problema encontrada com `os_fotos` (022).
-- Uso ADD COLUMN/CREATE TABLE IF NOT EXISTS pra ser seguro rodar em produção
-- (onde tudo isso já existe, vira no-op) e também deixar qualquer ambiente
-- novo/dev com o schema real.

-- =====================================================
-- EMPRESAS — campos do perfil público / diretório / licenciamento
-- =====================================================
ALTER TABLE `empresas`
  ADD COLUMN IF NOT EXISTS `idioma` VARCHAR(10) NOT NULL DEFAULT 'pt_BR' AFTER `uf`,
  ADD COLUMN IF NOT EXISTS `plano_atual` VARCHAR(30) NULL AFTER `plano`,
  ADD COLUMN IF NOT EXISTS `creditos_os` INT NOT NULL DEFAULT 0 AFTER `plano_atual`,
  ADD COLUMN IF NOT EXISTS `licenca_ate` DATE NULL AFTER `trial_ate`,
  ADD COLUMN IF NOT EXISTS `slug` VARCHAR(120) NULL,
  ADD COLUMN IF NOT EXISTS `descricao_publica` TEXT NULL,
  ADD COLUMN IF NOT EXISTS `foto_capa` VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS `site_url` VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS `instagram` VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS `email_publico` VARCHAR(120) NULL,
  ADD COLUMN IF NOT EXISTS `facebook` VARCHAR(150) NULL,
  ADD COLUMN IF NOT EXISTS `youtube` VARCHAR(150) NULL,
  ADD COLUMN IF NOT EXISTS `tiktok` VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS `whatsapp_publico` VARCHAR(20) NULL,
  ADD COLUMN IF NOT EXISTS `listagem_publica` TINYINT(1) DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `especialidades` TEXT NULL,
  ADD COLUMN IF NOT EXISTS `diretorio_destaque` ENUM('none','basico','premium') DEFAULT 'none',
  ADD COLUMN IF NOT EXISTS `diretorio_destaque_ate` DATE NULL,
  ADD COLUMN IF NOT EXISTS `latitude` DECIMAL(10,7) NULL,
  ADD COLUMN IF NOT EXISTS `longitude` DECIMAL(10,7) NULL,
  ADD COLUMN IF NOT EXISTS `reivindicada` TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `visitas` INT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `financeiro_inicio` DATE NULL,
  ADD COLUMN IF NOT EXISTS `tipo_conta` ENUM('completo','diretorio') NOT NULL DEFAULT 'completo',
  ADD COLUMN IF NOT EXISTS `creditos_scan_equip` INT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `creditos_scan_placa` INT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE `empresas`
  ADD INDEX IF NOT EXISTS `idx_slug` (`slug`),
  ADD INDEX IF NOT EXISTS `idx_publico` (`ativo`,`listagem_publica`,`uf`),
  ADD INDEX IF NOT EXISTS `idx_cidade` (`cidade`);

-- =====================================================
-- MARKETPLACE_ANUNCIOS — condição do item + imagens + slug
-- =====================================================
ALTER TABLE `marketplace_anuncios`
  ADD COLUMN IF NOT EXISTS `condicao` VARCHAR(50) DEFAULT 'Testada e funcionando' AFTER `codigo_interno`,
  ADD COLUMN IF NOT EXISTS `imagem_principal` VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS `imagens_galeria` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL
    CHECK (JSON_VALID(`imagens_galeria`)),
  ADD COLUMN IF NOT EXISTS `slug` VARCHAR(160) NULL;

-- =====================================================
-- DIRETÓRIO PÚBLICO — assinaturas, avaliações, banners, planos,
-- reivindicações de perfil e contagem diária de visitas
-- =====================================================

CREATE TABLE IF NOT EXISTS `diretorio_assinaturas` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `empresa_id` INT NOT NULL,
  `plano_id` INT NOT NULL,
  `status` ENUM('pendente','ativo','expirado','cancelado') DEFAULT 'pendente',
  `data_inicio` DATE NULL,
  `data_fim` DATE NULL,
  `valor_pago` DECIMAL(10,2) NULL,
  `comprovante` VARCHAR(255) NULL,
  `observacoes` TEXT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `diretorio_avaliacoes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `empresa_id` INT NOT NULL,
  `os_id` INT UNSIGNED NULL,
  `nome` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NULL,
  `nota` TINYINT NOT NULL,
  `comentario` TEXT NULL,
  `aprovado` TINYINT(1) DEFAULT 0,
  `verificada` TINYINT(1) NOT NULL DEFAULT 0,
  `resposta` TEXT NULL,
  `resposta_em` DATETIME NULL,
  `situacao` ENUM('publicada','contestada','oculta') NOT NULL DEFAULT 'publicada',
  `contestacao_motivo` TEXT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_diraval_os` (`os_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `diretorio_banners` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `assinatura_id` INT NOT NULL,
  `empresa_id` INT NOT NULL,
  `posicao` INT NOT NULL,
  `titulo` VARCHAR(150) NULL,
  `imagem` VARCHAR(255) NULL,
  `link_url` VARCHAR(255) NULL,
  `codigo_html` TEXT NULL,
  `aprovado` TINYINT(1) DEFAULT 0,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `diretorio_planos` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(100) NOT NULL,
  `tipo` ENUM('destaque','banner') NOT NULL,
  `descricao` TEXT NULL,
  `preco` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `duracao_dias` INT NOT NULL DEFAULT 30,
  `posicao_banner` INT NULL,
  `beneficios` TEXT NULL,
  `ativo` TINYINT(1) DEFAULT 1,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `diretorio_reivindicacoes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `empresa_id` INT NOT NULL,
  `nome` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `whatsapp` VARCHAR(20) NULL,
  `senha_hash` VARCHAR(255) NOT NULL,
  `status` ENUM('pendente','aprovada','rejeitada') NOT NULL DEFAULT 'pendente',
  `criado_em` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `processado_em` TIMESTAMP NULL DEFAULT NULL,
  `cnpj_informado` VARCHAR(18) NULL,
  PRIMARY KEY (`id`),
  KEY `empresa_id` (`empresa_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `diretorio_visitas` (
  `empresa_id` INT UNSIGNED NOT NULL,
  `dia` DATE NOT NULL,
  `total` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`empresa_id`,`dia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
