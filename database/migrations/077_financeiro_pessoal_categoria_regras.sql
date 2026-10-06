-- Financeiro pessoal — "beneficiário → categoria" aprendido, pra leitura automática de conta
-- (VisionService::lerConta()) acertar a categoria cada vez melhor com o uso, mesma ideia da
-- spec (financas-claude-code.md, seção 3: "Aprendizado: guardar beneficiário → categoria
-- sempre que o usuário trocar a sugestão e usar isso primeiro na próxima vez").
-- Escopado por usuario_id, mesmo domínio pessoal do resto desta área.

CREATE TABLE IF NOT EXISTS `financeiro_pessoal_categoria_regras` (
  `id`                        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`                INT UNSIGNED NOT NULL,
  -- Nome do beneficiário/descrição normalizado (minúsculo, sem acento, só [a-z0-9 ]) — texto
  -- livre não bate 1:1 entre leituras diferentes da mesma conta, a normalização é o que faz
  -- "Enel Distribuição" e "ENEL DISTRIBUIÇÃO SP" caírem na mesma regra.
  `beneficiario_normalizado`  VARCHAR(80) NOT NULL,
  `categoria`                 VARCHAR(40) NOT NULL,
  `atualizado_em`             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_usuario_benef` (`usuario_id`, `beneficiario_normalizado`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
