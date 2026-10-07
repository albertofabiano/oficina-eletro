-- Fixa Fase 1 — tabela `contas` (conta corrente, poupança, carteira em dinheiro, cartão de
-- crédito, investimento) — de onde o "saldo atual"/"saldo previsto" de um perfil é calculado
-- (saldo_inicial + receitas pagas − despesas pagas, ver app/Helpers/functions.php,
-- fixa_saldo_atual()). Todo lançamento passa a ter uma conta (ver 085_financeiro_pessoal_
-- lancamentos_perfil.sql, `conta_id`) — migração de dados cria uma conta "Carteira" por
-- usuário/perfil pra ligar os lançamentos já existentes, que nunca tiveram conta nenhuma.
--
-- `usuario_id` redundante com `perfil_id -> perfis.usuario_id` de propósito (mesmo padrão já
-- usado nas outras tabelas deste módulo) — permite filtrar/isolar direto por usuário sem JOIN,
-- e serve de segunda camada de defesa nos testes de isolamento entre usuários.
CREATE TABLE IF NOT EXISTS `financeiro_pessoal_contas` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`          INT UNSIGNED NOT NULL,
  `perfil_id`           INT UNSIGNED NOT NULL,
  `nome`                VARCHAR(80) NOT NULL,
  `tipo`                ENUM('corrente','poupanca','dinheiro','cartao_credito','investimento') NOT NULL DEFAULT 'corrente',
  `saldo_inicial`       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `data_saldo_inicial`  DATE NOT NULL,
  `cor`                 VARCHAR(30) NOT NULL DEFAULT '#3CC9C0',
  `arquivada`           TINYINT(1) NOT NULL DEFAULT 0,
  `criado_em`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_perfil` (`perfil_id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`perfil_id`) REFERENCES `financeiro_pessoal_perfis`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
