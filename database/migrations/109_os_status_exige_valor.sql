-- Comportamento novo em os_status, ao lado de permite_fechar/sem_valor/fecha_sem_cobranca/
-- motivo_fechamento/descarta_padrao: achado real (OS 0684, status nativo "Aprovado") — nada
-- impedia mudar o status de uma OS pra um status que representa "orçamento aprovado" mesmo com
-- `valor_total = 0` (nenhum serviço/peça lançado ainda), deixando a OS com o card Financeiro
-- mostrando "Total R$ 0,00 / Sem débito" apesar do nome do status dizer que foi aprovada.
--
-- `exige_valor`: quando marcado nesse status, a troca pra ele é BLOQUEADA (sem confirmação,
-- diferente de `fecha_sem_cobranca`/fechamento pendente, que só avisam) enquanto a OS não tiver
-- `valor_total > 0`. Não é restrito a nenhum `tipo` específico — qualquer status pode marcar,
-- não só os de tipo=aberta.
ALTER TABLE `os_status`
  ADD COLUMN IF NOT EXISTS `exige_valor` TINYINT(1) NOT NULL DEFAULT 0 AFTER `descarta_padrao`;
