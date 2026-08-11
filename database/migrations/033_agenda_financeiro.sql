-- Ponte opcional entre Agenda e Financeiro: um evento de agenda tipo=financeiro pode carregar
-- um "molde" de lançamento (tipo/valor/categoria/conta). Sem preencher o molde, o evento
-- continua sendo só um lembrete puro, exatamente como já era (ex.: "Pagamento do Aluguel",
-- "Pagamento AP Eliete") — nada muda pra quem não usar esse recurso.
--
-- Quando o molde está preenchido, uma ação "Marcar como pago/recebido" (ver
-- AgendaController) cria o lançamento de verdade no Fluxo de Caixa, vinculado de volta ao
-- evento via fin_lancamentos.agenda_id — sempre com 1 clique de confirmação, nunca automático
-- (o valor pode variar mês a mês, ex. aluguel reajustado, então não posta sozinho).
--
-- Recorrência continua vivendo só na Agenda (RRULE) — fin_lancamentos.recorrente/
-- recorrencia_tipo/grupo_parcela (migration 001) nunca foram usados por nenhum código e
-- continuam mortos de propósito, pra não ter dois motores de recorrência divergentes.

ALTER TABLE `agenda`
  ADD COLUMN `fin_tipo` ENUM('receita','despesa') DEFAULT NULL AFTER `alerta_sonoro`,
  ADD COLUMN `fin_valor` DECIMAL(10,2) DEFAULT NULL AFTER `fin_tipo`,
  ADD COLUMN `fin_categoria_id` INT UNSIGNED DEFAULT NULL AFTER `fin_valor`,
  ADD COLUMN `fin_conta_id` INT UNSIGNED DEFAULT NULL AFTER `fin_categoria_id`,
  ADD FOREIGN KEY (`fin_categoria_id`) REFERENCES `fin_categorias`(`id`) ON DELETE SET NULL,
  ADD FOREIGN KEY (`fin_conta_id`) REFERENCES `fin_contas`(`id`) ON DELETE SET NULL;

ALTER TABLE `fin_lancamentos`
  ADD COLUMN `agenda_id` INT UNSIGNED DEFAULT NULL AFTER `os_id`,
  ADD FOREIGN KEY (`agenda_id`) REFERENCES `agenda`(`id`) ON DELETE SET NULL;
