-- Rastreamento de região de quem visita o perfil público de uma empresa no Diretório.
-- Pedido do usuário: saber de quais cidades/estados vêm as visualizações do perfil, não só
-- o total/por dia que `diretorio_visitas` já guarda.
--
-- Desenho em duas tabelas, de propósito, pra nunca bloquear a resposta da página nem
-- arriscar crawl budget do Google (ver CLAUDE.md "risco pra SEO" — geolocalizar por IP de
-- forma síncrona, a cada requisição, atrasaria até o Googlebot, que nunca carrega sessão/
-- cookie de volta e por isso nunca seria "deduplicado" pelo mesmo mecanismo que já protege
-- visita humana repetida):
--
-- 1. `diretorio_visitas_ip_pendente` — fila. DiretorioController::empresa() só grava o IP
--    aqui (um INSERT rápido, sem chamada externa nenhuma) quando a visita já passou pelo
--    mesmo filtro de robô/dedup de sessão que o contador de visitas já usa.
-- 2. `diretorio_visitas_regiao` — resultado agregado (empresa_id + cidade + uf → total).
--    scripts/resolver_geo_visitas_diretorio.php (cron, fora do ciclo de requisição) resolve
--    a fila em lote contra uma API de geolocalização por IP, soma no agregado e APAGA a
--    linha da fila — o IP cru nunca fica guardado além do necessário pra resolver (minimização
--    de dado, mesma cautela já usada noutras partes do projeto com dado sensível).
CREATE TABLE IF NOT EXISTS `diretorio_visitas_ip_pendente` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `ip` VARCHAR(45) NOT NULL,
  `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `empresa_id` (`empresa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `diretorio_visitas_regiao` (
  `empresa_id` INT UNSIGNED NOT NULL,
  `cidade` VARCHAR(100) NOT NULL,
  `uf` CHAR(2) NOT NULL,
  `total` INT UNSIGNED NOT NULL DEFAULT 0,
  `atualizado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`empresa_id`, `cidade`, `uf`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
