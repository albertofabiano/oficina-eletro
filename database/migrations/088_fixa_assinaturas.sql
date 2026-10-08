-- Fixa (financeiro pessoal + agenda) vendido STANDALONE — assinatura própria por USUÁRIO (não
-- por empresa: cobre quem nunca teve conta de assistência técnica, só quer o financeiro
-- pessoal — ver "Cadastro próprio e simples" decidido com o usuário). Quem já tem Fixa de graça
-- por um plano pago do FixaOS (ver financeiro_pessoal_liberado()) não precisa de linha aqui.
--
-- payment_method_ref: só o TOKEN do gateway (nunca número de cartão) — qual gateway ainda está
-- em aberto (ver conversa), por isso fica NULL até a integração real existir; o teste grátis
-- funciona sem ele (é por isso que fica NULL-ável).
CREATE TABLE IF NOT EXISTS `fixa_assinaturas` (
  `id`                       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`               INT UNSIGNED NOT NULL,
  `plano`                    VARCHAR(30) NOT NULL,
  `ciclo`                    VARCHAR(20) NOT NULL DEFAULT 'mensal',
  `status`                   ENUM('teste','ativa','inadimplente','bloqueada','cancelada') NOT NULL DEFAULT 'teste',
  `teste_inicio`             DATETIME NULL,
  `teste_fim`                DATETIME NULL,
  `data_inicio`              DATE NULL,
  `data_fim`                 DATE NULL,
  `valor_centavos`           INT NOT NULL DEFAULT 0,
  `credito_centavos`         INT NOT NULL DEFAULT 0,
  `tentativas_falhas`        INT NOT NULL DEFAULT 0,
  `ultima_tentativa_em`      DATETIME NULL,
  `payment_method_ref`       VARCHAR(100) NULL,
  `indicado_por_usuario_id`  INT UNSIGNED NULL,
  `bloqueada_em`             DATETIME NULL,
  `cancelada_em`             DATETIME NULL,
  `criado_em`                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_status` (`status`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`indicado_por_usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Log de e-mails/avisos já enviados (dia 5 do teste, cada tentativa de cobrança) — evita
-- reenviar o mesmo aviso duas vezes se o cron rodar mais de uma vez no mesmo dia.
CREATE TABLE IF NOT EXISTS `fixa_assinatura_avisos` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `assinatura_id`   INT UNSIGNED NOT NULL,
  `tipo`            VARCHAR(40) NOT NULL,
  `enviado_em`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_assinatura_tipo` (`assinatura_id`, `tipo`),
  FOREIGN KEY (`assinatura_id`) REFERENCES `fixa_assinaturas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Leituras do scanner de contas no mês corrente — reseta sozinho (referencia_mes muda, a
-- contagem fica implícita em COUNT(*) WHERE referencia_mes = mês atual), separado dos créditos
-- avulsos comprados (creditos_scan_equip/_placa em `empresas`, que são saldo que NÃO reseta).
CREATE TABLE IF NOT EXISTS `fixa_scanner_leituras` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`      INT UNSIGNED NOT NULL,
  `referencia_mes`  CHAR(7) NOT NULL,
  `criado_em`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_usuario_mes` (`usuario_id`, `referencia_mes`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Registro de uso de IA (qualquer chamada à Anthropic, não só o scanner do Fixa) — custo
-- calculado a partir do `usage` real da resposta × preço configurado por modelo
-- (config/ia_precos.php), nunca um valor fixo chutado por leitura.
CREATE TABLE IF NOT EXISTS `ia_uso_log` (
  `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`        INT UNSIGNED NULL,
  `empresa_id`        INT UNSIGNED NULL,
  `modelo`            VARCHAR(60) NOT NULL,
  `contexto`          VARCHAR(60) NOT NULL,
  `tokens_entrada`    INT UNSIGNED NOT NULL DEFAULT 0,
  `tokens_saida`      INT UNSIGNED NOT NULL DEFAULT 0,
  `custo_centavos`    DECIMAL(10,4) NOT NULL DEFAULT 0,
  `criado_em`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_criado` (`criado_em`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_modelo` (`modelo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
