-- Financeiro pessoal — base técnica do piloto discutido (gasto PESSOAL do dono/funcionário,
-- separado de propósito do financeiro da EMPRESA). Por isso é escopado por `usuario_id`, não
-- `empresa_id` — diferente de toda outra tabela do sistema (ver nota no topo de CLAUDE.md:
-- "praticamente toda tabela tem empresa_id"). `fin_lancamentos` (empresa) não é tocado nem
-- reaproveitado — são domínios deliberadamente sem relação nenhuma no banco.
--
-- Acesso (ver `financeiro_pessoal_liberado()`, app/Helpers/functions.php) hoje é só o lado
-- GRÁTIS já combinado: empresa reivindicada + plano Oficina/Empresa. A assinatura paga avulsa
-- (R$19,90, pra quem não se qualifica de graça) ainda não tem cobrança integrada — não faz
-- parte desta rodada, só entra depois que o piloto confirmar demanda real.

CREATE TABLE IF NOT EXISTS `financeiro_pessoal_lancamentos` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL,
  `tipo`       ENUM('receita','despesa') NOT NULL DEFAULT 'despesa',
  `categoria`  VARCHAR(40) NOT NULL DEFAULT 'outros',
  `descricao`  VARCHAR(150) NOT NULL,
  `valor`      DECIMAL(10,2) NOT NULL,
  `data_hora`  DATETIME NOT NULL,
  -- 'foto': veio da leitura automática (comprovante/print). 'manual': digitado. Mesmo campo
  -- que vai alimentar o "93% dos gastos vieram de foto" do painel, quando a UI existir.
  `origem`     ENUM('manual','foto') NOT NULL DEFAULT 'manual',
  `criado_em`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_usuario_data` (`usuario_id`, `data_hora`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
