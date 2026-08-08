-- Recorrência de eventos de agenda ("Parcela AP", "Aluguel" etc. sem precisar criar um a um).
--
-- Modelo: a regra fica em `rrule` (RFC 5545) só na linha "mestre" da série — data_inicio do
-- mestre funciona como DTSTART. As ocorrências NÃO são materializadas em linhas: são geradas
-- sob demanda pra janela visível (ver agenda_rrule_expandir() em app/Helpers/rrule.php).
--
-- Exceções (editar/excluir uma ocorrência isolada sem afetar a série) viram uma linha própria
-- de `agenda`, ligada ao mestre por `recorrencia_id` + `recorrencia_data_original` (a data da
-- ocorrência original que essa linha substitui):
--   - recorrencia_excluida = 0  -> ocorrência editada (linha tem os campos novos)
--   - recorrencia_excluida = 1  -> ocorrência excluída (equivalente a um EXDATE)
-- Uma linha de exceção nunca tem `rrule` (não é ela mesma uma série).

ALTER TABLE `agenda`
  ADD COLUMN `rrule` VARCHAR(255) DEFAULT NULL AFTER `status`,
  ADD COLUMN `recorrencia_id` INT UNSIGNED DEFAULT NULL AFTER `rrule`,
  ADD COLUMN `recorrencia_data_original` DATE DEFAULT NULL AFTER `recorrencia_id`,
  ADD COLUMN `recorrencia_excluida` TINYINT(1) DEFAULT 0 AFTER `recorrencia_data_original`,
  ADD INDEX `idx_agenda_recorrencia_id` (`recorrencia_id`),
  ADD CONSTRAINT `fk_agenda_recorrencia` FOREIGN KEY (`recorrencia_id`) REFERENCES `agenda`(`id`) ON DELETE CASCADE;
