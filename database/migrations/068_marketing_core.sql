-- Módulo Marketing (tráfego pago) — opcional por empresa, ver CLAUDE.md ("Módulo Marketing").
-- Fase 1: coleta e painel de Meta Ads. Ver especificacao-modulo-marketing-fixaos.md pro
-- desenho completo; esta migration cobre só a fundação (Etapa 1): contas conectadas,
-- credenciais criptografadas, campanhas e métricas diárias espelhadas da plataforma.
--
-- `marketing_habilitado=0` por padrão: empresa sem o módulo não aparece em nenhuma consulta
-- de cron nem gera nenhuma chamada externa — mesmo princípio de "opcional, isolado" já usado
-- em outros módulos do sistema.
ALTER TABLE empresas ADD COLUMN IF NOT EXISTS marketing_habilitado TINYINT(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS mkt_ad_accounts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT UNSIGNED NOT NULL,
  platform ENUM('meta','google_ads','fake') NOT NULL,
  external_id VARCHAR(60) NOT NULL,
  name VARCHAR(150) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'BRL',
  status ENUM('active','disconnected') NOT NULL DEFAULT 'active',
  last_synced_at DATETIME NULL,
  last_sync_error VARCHAR(500) NULL COMMENT 'Mensagem sem token nenhum — ver CredentialCipher/describeMetaError.',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_mkt_ad_account (empresa_id, platform, external_id),
  FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Modelo agência: normalmente 1 linha global (scope='global') com o token do usuário de
-- sistema da FixaOS na Meta, compartilhado entre empresas conectadas. `scope='empresa'`
-- fica pronto pro caso raro de uma empresa trazer o próprio token (OAuth direto, futuro).
CREATE TABLE IF NOT EXISTS mkt_credentials (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  scope ENUM('global','empresa') NOT NULL,
  empresa_id INT UNSIGNED NULL,
  platform ENUM('meta','google_ads') NOT NULL,
  token_ciphertext BLOB NOT NULL,
  token_nonce BINARY(24) NOT NULL COMMENT 'Nonce do sodium_crypto_secretbox, 24 bytes.',
  key_version INT UNSIGNED NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mkt_campaigns (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT UNSIGNED NOT NULL,
  ad_account_id INT UNSIGNED NOT NULL,
  external_id VARCHAR(60) NOT NULL,
  name VARCHAR(190) NOT NULL,
  status ENUM('active','paused','archived') NOT NULL,
  daily_budget_cents BIGINT UNSIGNED NULL COMMENT 'NULL quando o orçamento vive no conjunto de anúncios, não na campanha.',
  synced_at DATETIME NOT NULL,
  UNIQUE KEY uq_mkt_campaign (ad_account_id, external_id),
  FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
  FOREIGN KEY (ad_account_id) REFERENCES mkt_ad_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Uma linha por campanha por dia; upsert por (campaign_id, date) — recoleta sempre
-- sobrescreve (a atribuição da Meta muda depois que o dia passa).
CREATE TABLE IF NOT EXISTS mkt_daily_insights (
  campaign_id INT UNSIGNED NOT NULL,
  `date` DATE NOT NULL,
  empresa_id INT UNSIGNED NOT NULL,
  spend_cents BIGINT UNSIGNED NOT NULL DEFAULT 0,
  impressions BIGINT UNSIGNED NOT NULL DEFAULT 0,
  clicks BIGINT UNSIGNED NOT NULL DEFAULT 0,
  leads INT UNSIGNED NOT NULL DEFAULT 0,
  collected_at DATETIME NOT NULL,
  PRIMARY KEY (campaign_id, `date`),
  FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
  FOREIGN KEY (campaign_id) REFERENCES mkt_campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
