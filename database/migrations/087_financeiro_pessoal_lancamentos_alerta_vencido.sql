-- Fixa Fase 1 — alerta em modal pra lançamento vencido sem marcar como pago, repetindo de 3 em
-- 3 horas enquanto não for resolvido. Mesmo padrão já usado no sistema principal pro alerta de
-- evento de agenda não concluído (ver 045_agenda_alerta_pendente.sql): só o carimbo do último
-- disparo, sem fila própria — a fonte da verdade continua sendo o próprio lançamento
-- (vencimento/pago_em), nunca uma cópia numa tabela de notificação à parte.
ALTER TABLE `financeiro_pessoal_lancamentos`
  ADD COLUMN `ultimo_alerta_vencido_em` DATETIME NULL AFTER `pago_em`;
