-- Financeiro pessoal, Fase 2 (financas-claude-code.md): "Contas e débitos" — listas de contas
-- a pagar (despesa recorrente tipo "Contas da casa") ou dívidas parceladas (tipo "Débitos e
-- parcelas"), cada uma com itens que podem ser marcados como pagos. Mesmo escopo por
-- usuario_id do resto desta área (ver comentário em 075_financeiro_pessoal.sql) — é o
-- financeiro PESSOAL do dono/funcionário, não da empresa.

CREATE TABLE IF NOT EXISTS `financeiro_pessoal_listas` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL,
  `nome`       VARCHAR(80) NOT NULL,
  `tipo`       ENUM('despesa','debito') NOT NULL DEFAULT 'despesa',
  `posicao`    INT NOT NULL DEFAULT 0,
  -- Estado aberta/fechada (recolhida) do card na tela — persiste por usuário, pedido explícito
  -- da spec ("Estado aberto/fechado de cada lista persiste por usuário").
  `aberta`     TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_usuario` (`usuario_id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `financeiro_pessoal_itens` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `lista_id`       INT UNSIGNED NOT NULL,
  `nome`           VARCHAR(120) NOT NULL,
  `valor`          DECIMAL(10,2) NOT NULL,
  `vencimento`     DATE NULL,
  -- NULL = em aberto (ainda não paga); preenchido = paga, e é o que faz a linha ficar riscada/
  -- verde na tela e gera o lançamento de saída vinculado (ver financeiro_pessoal_lancamentos.
  -- item_id abaixo).
  `pago_em`        DATETIME NULL,
  `categoria`       VARCHAR(40) NOT NULL DEFAULT 'outros',
  -- 'ocr'/'pix'/'boleto'/'consumo' ainda não têm nenhum caminho de código que grave isso
  -- (entram na Fase 3, scanner) — enum já definido agora pra não precisar de outro ALTER
  -- quando o scanner existir, mesmo princípio já usado nos tokens de cor da Fase 1.
  `origem`         ENUM('manual','pix','boleto','consumo','ocr') NOT NULL DEFAULT 'manual',
  `codigo_barras`  VARCHAR(80) NULL,
  `parcela`        VARCHAR(10) NULL,
  `posicao`        INT NOT NULL DEFAULT 0,
  `criado_em`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_lista` (`lista_id`),
  FOREIGN KEY (`lista_id`) REFERENCES `financeiro_pessoal_listas`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Liga um lançamento (saída) ao item de lista que o gerou, quando o item é marcado como pago —
-- regra da spec: "marcar um item como pago cria o lançamento de saída correspondente (e
-- desmarcar remove), para o saldo bater com as listas". ON DELETE SET NULL: excluir o item não
-- apaga o histórico do lançamento já gerado, só solta o vínculo (mesmo padrão já usado em
-- fin_lancamentos.agenda_id, ver CLAUDE.md).
ALTER TABLE `financeiro_pessoal_lancamentos`
  ADD COLUMN IF NOT EXISTS `item_id` INT UNSIGNED NULL AFTER `origem`;

-- Statement separado (não dá pra combinar com ADD COLUMN IF NOT EXISTS de forma portável) —
-- se a migration for rodada 2x por engano, esta linha falha com "Duplicate foreign key"; nesse
-- caso só ignore o erro desta linha específica, a coluna/índice acima já são idempotentes.
ALTER TABLE `financeiro_pessoal_lancamentos`
  ADD CONSTRAINT `fk_fplancamentos_item` FOREIGN KEY (`item_id`) REFERENCES `financeiro_pessoal_itens`(`id`) ON DELETE SET NULL;
