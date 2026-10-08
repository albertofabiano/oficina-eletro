-- Contas recorrentes do Carteira Fixa (pedido do usuário: "cadastro de conta recorrente, para
-- aluguel por exemplo, linkando com a agenda e notificando"). É um MOLDE (descrição, valor,
-- categoria, dia do vencimento) — nunca um lançamento em si; a cada mês, um lançamento de
-- verdade em `financeiro_pessoal_lancamentos` é gerado a partir dele (ver
-- App\Services\Fixa\RecorrenteService::gerarPendentes()), com `recorrente_id` apontando de volta
-- pra esta tabela. Mesmo princípio de "nunca materializar ocorrência pra sempre, só o que já
-- está por vir" já documentado no sistema principal pra RRULE/lembretes de agenda — aqui, de
-- forma bem mais simples (mensal fixo, sem RRULE), só o mês atual + o próximo são garantidos a
-- cada geração.
--
-- Escopado por `usuario_id` + `perfil_id`, mesmo padrão de toda tabela deste módulo (ver
-- 075_financeiro_pessoal.sql). `conta_id` é opcional (igual em `financeiro_pessoal_lancamentos`)
-- — sem conta escolhida, a geração cai na mesma regra de "primeira conta do perfil" que
-- `FinanceiroPessoalController::contaValidaOuPadrao()` já usa pro lançamento manual.
CREATE TABLE IF NOT EXISTS `financeiro_pessoal_recorrentes` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`     INT UNSIGNED NOT NULL,
  `perfil_id`      INT UNSIGNED NOT NULL,
  `conta_id`       INT UNSIGNED NULL,
  `tipo`           ENUM('receita','despesa') NOT NULL DEFAULT 'despesa',
  `categoria`      VARCHAR(40) NOT NULL DEFAULT 'outros',
  `descricao`      VARCHAR(150) NOT NULL,
  -- Mesmo texto livre que vira `observacao` em cada lançamento gerado — "Notas extras" na UI
  -- (ver 093_financeiro_pessoal_lancamentos_recorrente.sql), não uma segunda coluna de notas.
  `notas`          VARCHAR(500) NULL,
  `valor`          DECIMAL(10,2) NOT NULL,
  `dia_vencimento` TINYINT UNSIGNED NOT NULL,
  `ativo`          TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_usuario_perfil` (`usuario_id`, `perfil_id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`perfil_id`) REFERENCES `financeiro_pessoal_perfis`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- FK de conta em statement separado (mesma cautela já documentada em 084/085 deste módulo — se
-- a migration rodar de novo por engano, só esta linha falha, "Duplicate foreign key", sem travar
-- o CREATE TABLE acima, que já é idempotente via IF NOT EXISTS).
ALTER TABLE `financeiro_pessoal_recorrentes`
  ADD KEY `idx_conta` (`conta_id`),
  ADD CONSTRAINT `fk_fprec_conta` FOREIGN KEY (`conta_id`) REFERENCES `financeiro_pessoal_contas`(`id`) ON DELETE SET NULL;
