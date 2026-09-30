-- Log genérico de disparo de WhatsApp em massa por empresa (campanhas de novidade/anúncio pra
-- base de clientes já cadastrados, enviadas pelo número da PLATAFORMA via
-- WhatsAppService::enviarTextoPlataforma() — diferente do WhatsApp de cada empresa, que fala
-- com o CLIENTE FINAL dela). Espelha `empresas_email_log` (mesma migration 055, mesmo
-- raciocínio): uma tabela reaproveitável por qualquer campanha futura, em vez de uma coluna
-- nova em `empresas` a cada campanha, e permite reexecutar o disparo com segurança (nunca
-- reenvia a mesma campanha pra quem já recebeu).

CREATE TABLE IF NOT EXISTS `empresas_whatsapp_log` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `campanha`   VARCHAR(60) NOT NULL,
  `enviado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_empresa_campanha` (`empresa_id`, `campanha`),
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
