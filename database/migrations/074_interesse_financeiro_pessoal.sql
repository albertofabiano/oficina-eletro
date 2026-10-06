-- Captura de interesse do teaser "Financeiro pessoal" (Empresa > Perfil Público) — pilota a
-- demanda do app de financeiro pessoal separado da empresa antes de construí-lo de verdade.
-- 1 linha por empresa (UNIQUE), não por clique — clicar de novo só confirma que já está na
-- lista, não duplica nem reconta interesse.

CREATE TABLE IF NOT EXISTS `interesse_financeiro_pessoal` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED NULL,
  `criado_em`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_empresa` (`empresa_id`),
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
