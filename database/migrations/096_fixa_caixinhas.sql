-- Carteira Fixa — módulo "Caixinhas" (reserva de dinheiro pra guardar, sem rendimento/CDI/
-- imposto/integração bancária, ver pedido do usuário). O app só lembra de transferir e avisa
-- enquanto não transferiu — nunca calcula nada financeiro além da soma simples de
-- depósito/retirada.
--
-- Deliberadamente NÃO usa `financeiro_pessoal_lancamentos`/`tipo='transferencia'` (esse tipo já
-- existe no ENUM desde a migration 085, mas nunca foi criado por nenhuma tela e nem
-- `FixaContasController::saldosPorConta()` nem `FinanceiroPessoalController::
-- saldoAtualDoPerfil()` contam esse tipo no saldo — campo reservado, não funcionalidade
-- pronta). Tabela própria garante por construção que depósito/retirada nunca aparece em
-- "Gasto no mês"/ranking de categoria/gráfico (que só iteram `financeiro_pessoal_lancamentos`).
--
-- Valores em CENTAVOS (INT), diferente do resto do módulo (que usa DECIMAL reais) — pedido
-- explícito do usuário, mantém a soma de saldo da caixinha sempre exata sem arredondamento de
-- ponto flutuante.
CREATE TABLE IF NOT EXISTS `caixinhas` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`     INT UNSIGNED NOT NULL,
  `perfil_id`      INT UNSIGNED NOT NULL,
  `nome`           VARCHAR(80) NOT NULL,
  `cor`            VARCHAR(30) NOT NULL DEFAULT '#8C7CFF',
  `icone`          VARCHAR(40) NOT NULL DEFAULT 'piggy-bank-fill',
  `meta_centavos`  INT UNSIGNED NULL,
  `data_meta`      DATE NULL,
  `arquivada`      TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_usuario_perfil` (`usuario_id`, `perfil_id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`perfil_id`) REFERENCES `financeiro_pessoal_perfis`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Saldo da caixinha = soma de 'deposito' − soma de 'retirada' (nunca gravado em coluna, mesmo
-- princípio de fixa_saldo_atual() — calculado na hora, ver CaixinhaService::saldoCaixinha()).
-- `conta_id` é a conta de origem (depósito) ou destino (retirada) do movimento — opcional
-- (`ON DELETE SET NULL`, mesmo padrão de `financeiro_pessoal_lancamentos.conta_id`) pra um
-- movimento não ficar com FK quebrada se a conta for excluída depois.
--
-- `transferido_banco` só faz sentido pra 'deposito' (o lembrete de "já tirei daqui, falta
-- levar pro banco/investimento") — fica em 0 (default) pra 'retirada' sempre, sem CHECK
-- condicional (MySQL não tem CHECK dependente de outra coluna de forma portável nas versões
-- que o projeto roda, mesma observação já documentada em 082_financeiro_pessoal_perfis.sql).
CREATE TABLE IF NOT EXISTS `caixinha_movimentos` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `caixinha_id`         INT UNSIGNED NOT NULL,
  `usuario_id`          INT UNSIGNED NOT NULL,
  `perfil_id`           INT UNSIGNED NOT NULL,
  `tipo`                ENUM('deposito','retirada') NOT NULL,
  `valor_centavos`      INT UNSIGNED NOT NULL,
  `data`                DATE NOT NULL,
  `conta_id`            INT UNSIGNED NULL,
  `transferido_banco`   TINYINT(1) NOT NULL DEFAULT 0,
  `observacao`          VARCHAR(300) NULL,
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_caixinha` (`caixinha_id`),
  KEY `idx_usuario_perfil` (`usuario_id`, `perfil_id`),
  FOREIGN KEY (`caixinha_id`) REFERENCES `caixinhas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- FK de conta_id em statement separado (mesma cautela já documentada em 084/085 deste módulo —
-- se a migration rodar de novo por engano, só esta linha falha, "Duplicate foreign key", sem
-- travar os CREATE TABLE acima, que já são idempotentes via IF NOT EXISTS).
ALTER TABLE `caixinha_movimentos`
  ADD KEY `idx_conta` (`conta_id`),
  ADD CONSTRAINT `fk_caixmov_conta` FOREIGN KEY (`conta_id`) REFERENCES `financeiro_pessoal_contas`(`id`) ON DELETE SET NULL;
