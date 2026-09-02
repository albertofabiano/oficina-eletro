-- Rate-limit de login — pedido do usuário depois de uma avaliação externa (Meta IA) sobre o
-- documento técnico do sistema apontar a ausência disso (confirmado no código: AuthController
-- nunca teve nenhum bloqueio por tentativa). Ver CLAUDE.md, "Fechando os débitos técnicos P1
-- de segurança".
--
-- Uma linha por "chave" — usamos duas chaves por tentativa de login (`ip:<ip>` e
-- `login:<valor digitado>`), pra cobrir os dois padrões de ataque: força bruta vindo de um
-- único IP contra várias contas, e credential stuffing contra UMA conta vindo de vários IPs.
-- `UNIQUE KEY` em `chave` permite `INSERT ... ON DUPLICATE KEY UPDATE` (upsert simples, sem
-- SELECT + INSERT/UPDATE em duas idas ao banco pro caminho comum).

CREATE TABLE IF NOT EXISTS `login_tentativas` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `chave` VARCHAR(191) NOT NULL,
  `tentativas` INT UNSIGNED NOT NULL DEFAULT 1,
  `primeira_tentativa` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultima_tentativa` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bloqueado_ate` DATETIME DEFAULT NULL,
  UNIQUE KEY `uq_login_tentativas_chave` (`chave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
