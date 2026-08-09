-- Alerta sonoro de agenda: toca um beep (via notificação in-app, ver
-- App\Services\Lembretes\AgendaLembreteService) exatamente no VENCIMENTO do evento (instante em
-- que data_inicio chega), independente dos checkboxes de "Lembrete interno (técnico)" — é um
-- gatilho próprio, ligado/desligado por evento no modal de criar/editar.
--
-- `agenda.alerta_sonoro`: liga o gatilho pra esse evento (herdado por ocorrências virtuais de
-- série, igual cor/lembretes; pode ser sobrescrito numa exceção materializada).
-- `agenda_lembretes_fila.som`: marca QUAL disparo da fila (sempre offset_minutos=0) deve tocar
-- som ao virar notificação — permite reaproveitar a mesma linha de fila que o checkbox "Na hora"
-- já geraria, sem duplicar disparo (ver uq_lembrete_disparo).

ALTER TABLE `agenda`
  ADD COLUMN `alerta_sonoro` TINYINT(1) NOT NULL DEFAULT 0 AFTER `cor`;

ALTER TABLE `agenda_lembretes_fila`
  ADD COLUMN `som` TINYINT(1) NOT NULL DEFAULT 0 AFTER `offset_minutos`;
