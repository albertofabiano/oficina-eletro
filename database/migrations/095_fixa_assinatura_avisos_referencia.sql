-- Aviso de vencimento do Carteira Fixa standalone (ver AssinaturaService::precisaAviso(),
-- scripts/avisar_teste_fixa_terminando.php) passou a cobrir DOIS vencimentos possíveis por
-- assinatura ao longo da vida dela: o fim do teste grátis (uma vez só) e, depois, o fim de
-- cada ciclo pago (se repete a cada renovação). A UNIQUE antiga (`assinatura_id`, `tipo`)
-- travava o aviso pra sempre na primeira vez que disparasse — rodar de novo num próximo
-- vencimento (ex.: ciclo mensal vencendo todo mês) nunca avisaria de novo, porque a linha já
-- existia pro mesmo `tipo` desde o primeiro aviso.
--
-- `referencia` guarda a DATA do vencimento que gerou aquele aviso (mesmo papel de
-- `empresa_avisos_vencimento.data_vencimento`, migration 094) — a UNIQUE passa a reabrir
-- elegibilidade sozinha a cada vencimento novo, sem precisar apagar nada manualmente.
ALTER TABLE `fixa_assinatura_avisos`
  ADD COLUMN `referencia` DATE NULL AFTER `tipo`;

-- MySQL/MariaDB não suporta "DROP INDEX IF EXISTS" com nome fixo de forma 100% portável em
-- toda versão, mas o nome da UNIQUE já é conhecido (criada na 088) — se a migration já rodou
-- antes (reentrância), o ALTER acima falha primeiro (coluna já existe) e o script para ali,
-- então não há risco de tentar recriar a UNIQUE duas vezes.
ALTER TABLE `fixa_assinatura_avisos`
  DROP INDEX `uq_assinatura_tipo`,
  ADD UNIQUE KEY `uq_assinatura_tipo_referencia` (`assinatura_id`, `tipo`, `referencia`);
