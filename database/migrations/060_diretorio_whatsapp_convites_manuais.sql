-- Lista de convites via WhatsApp curada manualmente pelo Master (nome + WhatsApp achados
-- "na mão" pela internet), em vez de confiar cegamente no telefone de leads_prospeccao
-- (dado de CNPJ da Receita, sem garantia de que o número é WhatsApp válido). Mesma instância
-- e mesmo limite diário compartilhado das outras duas frentes (ver DisparoWhatsappDiretorioService).
CREATE TABLE IF NOT EXISTS `diretorio_convites_manuais` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nome_empresa` VARCHAR(150) NOT NULL,
  `whatsapp` VARCHAR(15) NOT NULL,
  `enviado_em` DATETIME NULL,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_whatsapp` (`whatsapp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
