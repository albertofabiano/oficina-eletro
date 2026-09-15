-- Status de OS ganha 2 comportamentos configuráveis novos, ao lado de permite_fechar/sem_valor/
-- fecha_sem_cobranca: o comprovante e o texto de fechamento "sem cobrança" hoje adivinham se é
-- "Sem Conserto" ou "Recusado" só pelo NOME do status (str_contains no nome), o que é frágil se
-- a empresa nomear o status de um jeito que não bate com essas palavras. Agora dá pra configurar
-- explicitamente.
--
-- `motivo_fechamento`: NULL (padrão, comportamento de sempre — detecta pelo nome) | 'sem_conserto'
-- | 'recusado'. Mutuamente exclusivo por natureza (VARCHAR com só esses 2 valores possíveis,
-- validado em OsStatusController::salvar()) — só faz sentido combinado com sem_valor=1 ou
-- tipo=cancelada (mesma condição que já define $ehSemConserto em OrdemServicoController::fechar()).
--
-- `descarta_padrao`: quando marcado, fechar a OS a partir deste status já assume "equipamento
-- descartado" — pré-seleciona o rádio no modal manual, e é o que o fechamento AUTOMÁTICO
-- (fecha_sem_cobranca) usa pra decidir `ordens_servico.equipamento_descartado`, já que esse
-- caminho não passa por modal nenhum (lacuna documentada antes no CLAUDE.md — sempre caía no
-- default "devolvido").
ALTER TABLE `os_status`
  ADD COLUMN IF NOT EXISTS `motivo_fechamento` VARCHAR(20) NULL DEFAULT NULL AFTER `fecha_sem_cobranca`,
  ADD COLUMN IF NOT EXISTS `descarta_padrao` TINYINT(1) NOT NULL DEFAULT 0 AFTER `motivo_fechamento`;
