-- No fechamento "Sem Conserto/Recusado", o técnico agora escolhe se o equipamento vai ser
-- devolvido ao cliente (padrão, 0) ou descartado pela assistência (cliente não vai retirar) —
-- ver modal Fechar OS em os/show.php e OrdemServicoController::fechar(). Só é lido/gravado
-- quando a OS fecha sem cobrança; em qualquer outro fechamento fica no default.
ALTER TABLE `ordens_servico`
  ADD COLUMN `equipamento_descartado` TINYINT(1) NOT NULL DEFAULT 0;
