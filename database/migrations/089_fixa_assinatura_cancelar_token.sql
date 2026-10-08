-- Token do link de cancelamento em 1 clique (Etapa 3: "dia 5, aviso de 2 dias antes da cobrança
-- com link de cancelamento em 1 clique") — mesmo padrão já usado pelos links de descadastro de
-- e-mail do projeto (leads_prospeccao.email_unsub_token etc.): token aleatório opaco, nunca o id
-- da assinatura cru na URL, pra não dar pra adivinhar/varrer outras assinaturas pelo link.
ALTER TABLE `fixa_assinaturas`
  ADD COLUMN IF NOT EXISTS `cancelar_token` VARCHAR(40) NULL AFTER `payment_method_ref`,
  ADD UNIQUE KEY IF NOT EXISTS `uq_cancelar_token` (`cancelar_token`);
