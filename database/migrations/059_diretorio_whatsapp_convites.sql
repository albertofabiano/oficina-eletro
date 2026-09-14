-- Convite via WhatsApp pro Diretório (ver CLAUDE.md "Convite via WhatsApp pro Diretório
-- ('reivindique' ou 'cadastre-se')") — duas frentes que reaproveitam tabelas já existentes,
-- sem precisar de tabela nova de extração (diferente do e-mail, aqui a fonte já mora direto
-- nas tabelas-base):
--   - `empresas`: convite "reivindique seu perfil" pra quem já tem ficha publicada mas
--     ninguém logou pra gerenciar (reivindicada=0) — carimbo direto na própria linha.
--   - `leads_prospeccao`: convite "cadastre-se grátis" pra CNPJ que ainda nem tem ficha —
--     mesmo padrão da coluna irmã `email_convite_enviado_em` que essa tabela já tem.
ALTER TABLE `empresas`
  ADD COLUMN IF NOT EXISTS `whatsapp_convite_enviado_em` DATETIME NULL;

ALTER TABLE `leads_prospeccao`
  ADD COLUMN IF NOT EXISTS `whatsapp_convite_enviado_em` DATETIME NULL;
