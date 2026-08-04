-- Tabela usada por OrdemServicoController::persistirFotosEntrada() e ::acompanhar()
-- pra guardar as fotos do estado de entrada do equipamento, anexadas à OS. Ficou
-- de fora do schema inicial (001_schema.sql) por engano — nunca existiu em produção,
-- o que quebrava com "Base table or view not found" a página pública de
-- acompanhamento (/os/acompanhar/{token}) sempre que a OS tinha fotos.
CREATE TABLE IF NOT EXISTS `os_fotos` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `os_id` INT UNSIGNED NOT NULL,
  `arquivo` VARCHAR(255) NOT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`os_id`) REFERENCES `ordens_servico`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
