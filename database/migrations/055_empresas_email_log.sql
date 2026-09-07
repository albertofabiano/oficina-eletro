-- Log genérico de disparo de e-mail em massa por empresa (campanhas de novidade/anúncio pra
-- base de clientes já cadastrados — diferente de leads_prospeccao/diretorio_leads_email, que
-- são leads externos sem conta no sistema). Reaproveitável por qualquer campanha futura, não
-- só a de "novidades do sistema" que motivou esta migration — evita ter que criar uma coluna
-- nova em `empresas` a cada nova campanha, e permite reprocessar/reexecutar o script de disparo
-- com segurança (nunca reenvia a mesma campanha pra quem já recebeu).

CREATE TABLE IF NOT EXISTS `empresas_email_log` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT NOT NULL,
  `campanha`   VARCHAR(60) NOT NULL,
  `enviado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_empresa_campanha` (`empresa_id`, `campanha`),
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
