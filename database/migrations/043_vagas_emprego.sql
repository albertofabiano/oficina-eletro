-- Vagas de emprego (mural exclusivo pra empresas assinantes do FixaOS) — ver
-- VagasController e CLAUDE.md "Vagas de emprego". Candidato nunca cadastra nada no sistema:
-- contato é sempre direto pro WhatsApp da empresa (empresas.whatsapp), então não há tabela
-- de candidatura/currículo.
CREATE TABLE IF NOT EXISTS `vagas_emprego` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `titulo` VARCHAR(150) NOT NULL,
  `descricao` TEXT NOT NULL,
  `requisitos` TEXT NULL,
  `beneficios` TEXT NULL,
  `regime` ENUM('clt','pj','freelancer','estagio') NOT NULL DEFAULT 'clt',
  `jornada` ENUM('integral','meio_periodo','flexivel') NOT NULL DEFAULT 'integral',
  `modalidade` ENUM('presencial','remoto','hibrido') NOT NULL DEFAULT 'presencial',
  `nivel` ENUM('estagiario','junior','pleno','senior') NULL,
  `salario_min` DECIMAL(10,2) NULL,
  `salario_max` DECIMAL(10,2) NULL,
  `salario_a_combinar` TINYINT(1) NOT NULL DEFAULT 0,
  `cidade` VARCHAR(80) NULL,
  `uf` CHAR(2) NULL,
  `status` ENUM('aberta','encerrada') NOT NULL DEFAULT 'aberta',
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_vagas_empresa` (`empresa_id`),
  KEY `idx_vagas_status_uf_cidade` (`status`, `uf`, `cidade`),
  CONSTRAINT `fk_vagas_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
