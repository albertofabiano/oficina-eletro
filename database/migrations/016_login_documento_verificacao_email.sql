-- Confirmação de e-mail: usuários existentes já entram como verificados
-- (não travar quem já usa o sistema); só cadastros novos nascem em 0.
ALTER TABLE usuarios
  ADD COLUMN IF NOT EXISTS email_verificado TINYINT(1) NOT NULL DEFAULT 1 AFTER email,
  ADD COLUMN IF NOT EXISTS token_verificacao VARCHAR(64) NULL AFTER email_verificado,
  ADD COLUMN IF NOT EXISTS token_verificacao_expira DATETIME NULL AFTER token_verificacao;
