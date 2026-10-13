-- Cadastro próprio do Carteira Fixa virou promocional (pedido do usuário): sem tela de forma
-- de pagamento nenhuma, grátis por 1 ano pra quem se cadastra agora — `promo_vaga` guarda a
-- posição sequencial (1, 2, 3...) entre TODOS os cadastros promocionais, não só os primeiros
-- 100, pra sempre ser possível saber com exatidão quem de fato estava nos primeiros 100 (sem
-- isso, "primeiros 100 clientes" viraria uma alegação de marketing sem como confirmar depois —
-- ver AssinaturaService::criarPromocional()). NULL em qualquer assinatura criada por outro
-- caminho (ex.: criarTeste(), mantido intacto pra quando o cadastro pago voltar).
ALTER TABLE `fixa_assinaturas`
  ADD COLUMN IF NOT EXISTS `promo_vaga` INT UNSIGNED NULL AFTER `indicado_por_usuario_id`;
