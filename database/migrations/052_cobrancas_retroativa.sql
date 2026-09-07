-- Migration retroativa: `cobrancas` já existia em produção (motor de toda cobrança via
-- InfinitePay — assinatura do sistema, pacotes de crédito, anúncios do Diretório, todos pelo
-- mesmo webhook POST /webhook/infinitepay ramificado por `tipo`), mas nunca teve CREATE TABLE
-- commitado — mesma categoria de gap já documentada em CLAUDE.md pra `os_pagamentos` e
-- `lib/dompdf/vendor/`.
--
-- Schema confirmado via `DESCRIBE cobrancas;` direto em produção (2026-09-02) — a primeira
-- versão deste arquivo era reconstrução por inferência do código-fonte e errou em vários
-- pontos: `status` é ENUM de verdade (com um 4º valor, 'expirado', que nenhum código hoje
-- escreve mas existe no schema), `tipo` é NOT NULL com default 'assinatura' (não nullable
-- como a inferência assumiu), e vários tamanhos de VARCHAR/tipo de INT divergiam. Corrigido
-- pra bater exatamente com o `DESCRIBE` real antes de aplicar em qualquer ambiente.
--
-- `empresa_id` é `INT` simples (não UNSIGNED) no `DESCRIBE` real, diferente de `empresas.id`
-- (`INT UNSIGNED`) — por isso SEM `FOREIGN KEY` aqui: um InnoDB não cria FK entre colunas de
-- signedness diferente (mesmo erro 1005/150 já corrigido em `055_empresas_email_log.sql`), o
-- que confirma que a tabela real de produção nunca teve essa FK de fato — só o relacionamento
-- a nível de aplicação. Reconstruir com FK forçando UNSIGNED divergiria do schema real
-- confirmado; mantido fiel ao `DESCRIBE`, com um índice normal no lugar da FK.
--
-- `tipo` distingue o que a cobrança representa: 'assinatura' (plano do sistema completo),
-- 'credito' (pacote de crédito de OS/scan), 'diretorio' (assinatura de banner/destaque do
-- Diretório) — `plano` guarda um código que varia por tipo (código do plano puro pra
-- assinatura; prefixo tipo 'credito_25'/'creditoscanequip_10' pra crédito;
-- 'diretorio_{assinatura_id}' pra anúncio). `order_nsu` é a chave de idempotência do webhook
-- (nunca reprocessa a mesma cobrança duas vezes) — por isso UNIQUE.

CREATE TABLE IF NOT EXISTS `cobrancas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT NOT NULL,
  `tipo` VARCHAR(20) NOT NULL DEFAULT 'assinatura',
  `plano` VARCHAR(30) NOT NULL,
  `ciclo` VARCHAR(20) DEFAULT NULL,
  `dias` INT DEFAULT NULL,
  `valor` INT NOT NULL,
  `order_nsu` VARCHAR(50) NOT NULL,
  `status` ENUM('pendente','pago','expirado','cancelado') NOT NULL DEFAULT 'pendente',
  `link_url` TEXT DEFAULT NULL,
  `invoice_slug` VARCHAR(100) DEFAULT NULL,
  `transaction_nsu` VARCHAR(100) DEFAULT NULL,
  `capture_method` VARCHAR(20) DEFAULT NULL,
  `paid_amount` INT DEFAULT NULL,
  `receipt_url` TEXT DEFAULT NULL,
  `criado_em` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `pago_em` TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY `uq_cobrancas_order_nsu` (`order_nsu`),
  KEY `idx_cobrancas_empresa` (`empresa_id`),
  KEY `idx_cobrancas_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
