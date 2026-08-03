-- Permite até 2 sessões simultâneas por conta (antes era sessão única — ver
-- 010_sessao_unica.sql). Substitui a coluna usuarios.sessao_token (1 token só)
-- por uma tabela com histórico das sessões ativas; no login, mantém só as 2
-- mais recentes e derruba qualquer sessão mais antiga que isso.
CREATE TABLE IF NOT EXISTS `sessoes_ativas` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_token` (`token`),
  KEY `idx_usuario` (`usuario_id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
