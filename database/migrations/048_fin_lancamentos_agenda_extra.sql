-- Financeiro → Agenda (ver CLAUDE.md "Financeiro → Agenda (sentido inverso...)"): até aqui todo
-- lançamento manual pendente (sem os_id) virava evento na Agenda automaticamente, sem opção de
-- desligar nem de escolher a cor da etiqueta exibida lá. Duas colunas novas:
--   mostrar_agenda: liga/desliga a sincronização para este lançamento específico (default 1,
--     preserva o comportamento atual — "sempre sincroniza" — pra quem não mexer no campo novo).
--   cor_agenda: cor (hex) da etiqueta do evento na Agenda quando mostrar_agenda=1; NULL cai no
--     padrão do tipo "Financeiro" (config/eventos_agenda.php).
ALTER TABLE `fin_lancamentos`
  ADD COLUMN IF NOT EXISTS `mostrar_agenda` TINYINT(1) NOT NULL DEFAULT 1 AFTER `agenda_id`,
  ADD COLUMN IF NOT EXISTS `cor_agenda` VARCHAR(7) NULL AFTER `mostrar_agenda`;
