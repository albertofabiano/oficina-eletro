-- Financeiro pessoal — `origem` ganha 'voz' (lançamento criado por voz, ver
-- FinanceiroPessoalController::vozExtrair()) ao lado de 'manual'/'foto' já existentes. Mesmo
-- padrão já usado quando `tipo` ganhou 'transferencia' (085_financeiro_pessoal_lancamentos_
-- perfil.sql) — amplia o ENUM sem remapear nenhum valor antigo.
ALTER TABLE `financeiro_pessoal_lancamentos`
  MODIFY COLUMN `origem` ENUM('manual','foto','voz') NOT NULL DEFAULT 'manual';
