-- Permite ocultar um item da lista de "últimos 10 defeitos" sugeridos no wizard de Nova OS
-- (ver OrdemServicoController::defeitosSugeridos()) sem mexer nas OS reais que usaram aquele
-- texto — a lista é só uma sugestão derivada de `ordens_servico.defeito_relatado` via GROUP BY,
-- não um catálogo próprio; ocultar não deve apagar nem alterar nenhuma OS existente.
--
-- `defeito_hash` (MD5 do texto normalizado: minúsculo + sem espaço nas pontas) é a chave de
-- unicidade porque `defeito_relatado` é TEXT (sem tamanho fixo pra indexar direto). Guarda o
-- texto original também, só pra referência/depuração — não tem tela de "reexibir" hoje.
CREATE TABLE IF NOT EXISTS `os_defeitos_ocultos` (
  `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id`       INT UNSIGNED NOT NULL,
  `defeito_hash`     CHAR(32) NOT NULL,
  `defeito_relatado` TEXT NOT NULL,
  `ocultado_em`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_empresa_defeito` (`empresa_id`, `defeito_hash`),
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
