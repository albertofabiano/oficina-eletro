-- Migration retroativa: `cobrancas` já existia em produção (motor de toda cobrança via
-- InfinitePay — assinatura do sistema, pacotes de crédito, anúncios do Diretório, todos pelo
-- mesmo webhook POST /webhook/infinitepay ramificado por `tipo`), mas nunca teve CREATE TABLE
-- commitado — mesma categoria de gap já documentada em CLAUDE.md pra `os_pagamentos` e
-- `lib/dompdf/vendor/`.
--
-- Schema reconstruído a partir de todo INSERT/UPDATE/SELECT em `cobrancas` no código-fonte
-- (PagamentoController::assinar/comprarCredito/webhook, DiretorioAnunciosController::contratar,
-- MasterController — listagem de empresas) — não a partir de um DESCRIBE real de produção,
-- já que esta sessão não tem acesso ao banco. CREATE TABLE IF NOT EXISTS: rodar isso contra
-- produção é inofensivo (tabela já existe, migration não altera nada); só materializa a
-- tabela em ambientes novos. ANTES de aplicar em produção, rode `DESCRIBE cobrancas;` no VPS
-- pra confirmar que este schema bate com o real, especialmente o tipo de `tipo` (aqui como
-- VARCHAR livre — se no banco real for ENUM, um valor novo como 'diretorio' pode ter
-- precisado de ALTER TABLE na época, ver aviso já registrado em CLAUDE.md sobre esse mesmo
-- risco).
--
-- `tipo` distingue o que a cobrança representa: NULL/'assinatura' (plano do sistema completo),
-- 'credito' (pacote de crédito de OS/scan), 'diretorio' (assinatura de banner/destaque do
-- Diretório) — `plano` guarda um código que varia por tipo (código do plano puro pra
-- assinatura; prefixo tipo 'credito_25'/'creditoscanequip_10' pra crédito;
-- 'diretorio_{assinatura_id}' pra anúncio). `order_nsu` é a chave de idempotência do webhook
-- (nunca reprocessa a mesma cobrança duas vezes) — por isso UNIQUE.

CREATE TABLE IF NOT EXISTS `cobrancas` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `tipo` VARCHAR(20) DEFAULT NULL,
  `plano` VARCHAR(60) NOT NULL,
  `ciclo` VARCHAR(20) DEFAULT NULL,
  `dias` INT UNSIGNED DEFAULT NULL,
  `valor` INT UNSIGNED NOT NULL,
  `order_nsu` VARCHAR(60) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pendente',
  `link_url` TEXT DEFAULT NULL,
  `transaction_nsu` VARCHAR(60) DEFAULT NULL,
  `invoice_slug` VARCHAR(100) DEFAULT NULL,
  `capture_method` VARCHAR(30) DEFAULT NULL,
  `paid_amount` INT UNSIGNED DEFAULT NULL,
  `receipt_url` TEXT DEFAULT NULL,
  `pago_em` DATETIME DEFAULT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_cobrancas_order_nsu` (`order_nsu`),
  KEY `idx_cobrancas_empresa` (`empresa_id`),
  KEY `idx_cobrancas_status` (`status`),
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
