-- Suporta o novo formulário de Equipamento (etapa 2 do wizard Nova OS):
-- checklist de estado de entrada (texto composto, não mais um dos 5 valores fixos)
-- e um campo genérico de especificação (tela em polegadas para TV/Notebook,
-- capacidade para linha branca — nunca os dois ao mesmo tempo, por isso uma
-- coluna só resolve).
ALTER TABLE `equipamentos`
  MODIFY COLUMN `estado_entrada` VARCHAR(255) NULL,
  ADD COLUMN `especificacao` VARCHAR(60) NULL AFTER `voltagem`;
