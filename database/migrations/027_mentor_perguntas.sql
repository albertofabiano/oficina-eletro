-- Log de perguntas feitas ao Mentor (uma linha por pergunta), usado só pra contar
-- uso mensal por empresa e decidir silenciosamente qual modelo de IA usar
-- (ver MentorController::modeloPara()) -- nunca é exibido nem bloqueia o chat.
CREATE TABLE IF NOT EXISTS `mentor_perguntas` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_empresa_data` (`empresa_id`, `criado_em`),
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
