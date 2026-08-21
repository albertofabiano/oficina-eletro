-- "Alerta de pendência" — evento já passou do horário e ninguém marcou como concluído/
-- cancelado; re-notifica o técnico a cada 3h até alguém resolver (ver
-- App\Services\Lembretes\AgendaLembreteService::enviarAlertasPendentes() e CLAUDE.md
-- "Alerta de evento não concluído"). Só guarda o carimbo do último disparo — não precisa de
-- fila própria (diferente de agenda_lembretes_fila, que é pra disparo único por offset fixo).
ALTER TABLE `agenda`
  ADD COLUMN IF NOT EXISTS `ultimo_alerta_pendente_em` DATETIME NULL;
