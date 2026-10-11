-- Carteira Fixa — "formas de pagamento" (ROTEIRO-carteira-fixa.md do usuário, §2): substitui o
-- par campo-fixo `meio` (pix/débito/crédito) + entidades cartão/maquininha por um cadastro só,
-- nomeado pelo próprio usuário ("Nubank roxinho", "Pix Itaú"), com 3 comportamentos de data
-- possíveis — `tipo`:
--   simples   → não mexe na data (pix, débito, dinheiro, boleto)
--   cartao    → joga pro vencimento da fatura (dia_fechamento/dia_vencimento)
--   recebivel → joga pra data do repasse, D+N (prazo_dias)
--
-- `direcao` decide em qual lista a forma aparece (saida/entrada/ambas) — pix é 'ambas', cartão
-- de crédito é 'saida', maquininha é 'entrada'.
--
-- `conta_id` é OPCIONAL e é uma decisão de desenho desta sessão, não do roteiro (que não liga
-- forma a conta): se setado, um lançamento que usa essa forma herda essa conta direto ("Nubank"
-- sempre bate na conta corrente que paga a fatura); se não, cai no `contaValidaOuPadrao()` de
-- sempre — não trava o cadastro rápido de uma forma nova sem escolher conta.
--
-- `usos` ordena a lista de chips por mais usada primeiro (mesmo padrão já pedido pro roteiro) —
-- incrementado pelo controller a cada lançamento que usa a forma, nunca recalculado por query.
--
-- `usuario_id` redundante com `perfil_id -> perfis.usuario_id`, mesmo padrão de isolamento já
-- usado em toda tabela deste módulo (ver nota em 083_financeiro_pessoal_contas.sql).
CREATE TABLE IF NOT EXISTS `financeiro_pessoal_formas_pagamento` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`      INT UNSIGNED NOT NULL,
  `perfil_id`       INT UNSIGNED NOT NULL,
  `conta_id`        INT UNSIGNED NULL,
  `nome`            VARCHAR(80) NOT NULL,
  `tipo`            ENUM('simples','cartao','recebivel') NOT NULL DEFAULT 'simples',
  `direcao`         ENUM('saida','entrada','ambas') NOT NULL DEFAULT 'ambas',
  `dia_fechamento`  TINYINT UNSIGNED NULL,
  `dia_vencimento`  TINYINT UNSIGNED NULL,
  `prazo_dias`      SMALLINT UNSIGNED NULL DEFAULT 30,
  `cor`             VARCHAR(30) NOT NULL DEFAULT '#8C7CFF',
  `icone`           VARCHAR(40) NOT NULL DEFAULT 'wallet2',
  `usos`            INT UNSIGNED NOT NULL DEFAULT 0,
  `arquivada`       TINYINT(1) NOT NULL DEFAULT 0,
  `criado_em`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_usuario_perfil` (`usuario_id`, `perfil_id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`perfil_id`) REFERENCES `financeiro_pessoal_perfis`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- FK de conta em statement separado (mesma cautela já documentada em 084/085/092 deste módulo:
-- se a migration rodar de novo por engano, só esta linha falha, "Duplicate foreign key", sem
-- travar o CREATE TABLE acima, que já é idempotente via IF NOT EXISTS).
ALTER TABLE `financeiro_pessoal_formas_pagamento`
  ADD KEY `idx_conta` (`conta_id`),
  ADD CONSTRAINT `fk_fpform_conta` FOREIGN KEY (`conta_id`) REFERENCES `financeiro_pessoal_contas`(`id`) ON DELETE SET NULL;
