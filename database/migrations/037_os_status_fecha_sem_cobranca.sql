-- Status de OS ganha um 3o "comportamento" configurável (ao lado de permite_fechar/sem_valor):
-- ao mover uma OS pra um status com essa flag ligada, ela é fechada automaticamente sem
-- cobrança (mesmo destino/campos do fechamento manual "Sem Conserto/Recusado", ver
-- OrdemServicoController::fechar()) — só faz sentido pra status tipo='cancelada', validado no
-- controller (OsStatusController::salvar() força 0 se tipo != cancelada).
ALTER TABLE `os_status`
  ADD COLUMN `fecha_sem_cobranca` TINYINT(1) NOT NULL DEFAULT 0 AFTER `sem_valor`;
