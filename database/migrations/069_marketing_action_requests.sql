-- Módulo Marketing — Etapa 3: fila de aprovação + registro de auditoria de verdade.
-- Ver CLAUDE.md ("Módulo Marketing") e especificacao-modulo-marketing-fixaos.md, seções 4/8/9.
--
-- Regra inegociável: NENHUMA escrita numa plataforma de anúncio roda fora desta fila — toda
-- sugestão de regra ou pedido do usuário nasce 'pending' e só vira ação de verdade depois de
-- aprovação humana registrada aqui (decided_by/decided_at). SyncService::audit() já grava em
-- mkt_audit_log desde a Etapa 1/2, mas envolto em try/catch porque a tabela só passa a existir
-- de verdade agora — a partir desta migration, os registros de coleta também começam a aparecer.

CREATE TABLE IF NOT EXISTS mkt_action_requests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT UNSIGNED NOT NULL,
  campaign_id INT UNSIGNED NOT NULL,
  action_type ENUM('pause_campaign','resume_campaign','update_daily_budget') NOT NULL,
  payload LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL
    CHECK (payload IS NULL OR JSON_VALID(payload)) COMMENT 'orçamento: {"daily_budget_cents":N,"previous_daily_budget_cents":N}; pausar/retomar: {} ou null',
  reason VARCHAR(500) NOT NULL COMMENT 'explicação em português mostrada ao usuário na tela Aprovações',
  source ENUM('rule','user') NOT NULL DEFAULT 'rule',
  rule_id VARCHAR(40) NULL COMMENT 'ex.: pause-no-leads — null quando source=user',
  status ENUM('pending','approved','rejected','executed','failed') NOT NULL DEFAULT 'pending',
  requested_by INT UNSIGNED NULL COMMENT 'usuarios.id — null quando source=rule (o cron gerou)',
  requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decided_by INT UNSIGNED NULL,
  decided_at DATETIME NULL,
  executed_at DATETIME NULL,
  dry_run TINYINT(1) NULL COMMENT 'true quando executado em modo simulação (config/marketing.php: dry_run)',
  error VARCHAR(500) NULL COMMENT 'mensagem sem token nenhum — mesma disciplina de mkt_ad_accounts.last_sync_error',
  -- Trava "só 1 pedido pendente por campanha+ação" também no banco (não só no código):
  -- coluna gerada some (vira NULL) assim que o status deixa de ser 'pending', então só
  -- pedidos pendentes concorrem pela mesma chave única — MySQL trata múltiplos NULL como
  -- valores distintos, então histórico decidido nunca colide entre si.
  pending_key VARCHAR(80) GENERATED ALWAYS AS
    (IF(status = 'pending', CONCAT(campaign_id, ':', action_type), NULL)) STORED,
  UNIQUE KEY uq_mkt_action_pending (pending_key),
  KEY idx_mkt_action_empresa_status (empresa_id, status),
  FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
  FOREIGN KEY (campaign_id) REFERENCES mkt_campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mkt_audit_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empresa_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NULL COMMENT 'null quando a ação partiu do cron, não de um clique',
  action VARCHAR(40) NOT NULL COMMENT 'ex.: sync, sync_failed, suggestion_created, suggestion_approved, suggestion_rejected, suggestion_executed, suggestion_failed',
  entity_type VARCHAR(40) NOT NULL,
  entity_id INT UNSIGNED NOT NULL,
  details LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL
    CHECK (details IS NULL OR JSON_VALID(details)) COMMENT 'nunca contém token/credencial nenhuma',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_mkt_audit_empresa (empresa_id, created_at),
  FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
