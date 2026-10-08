-- Fixa (Carteira Fixa) — marca a conta criada AUTOMATICAMENTE junto com o perfil ("Carteira"
-- pra pf, "Conta da empresa" pra pj — ver PerfilService::criarPerfilPessoalPadrao()/criarPerfil())
-- como `padrao`, pra nunca poder ser arquivada: todo perfil precisa de pelo menos 1 conta
-- utilizável, e é essa que o sistema garante que sempre existe. Contas criadas DEPOIS, pelo
-- próprio usuário ("+ Nova conta"), nascem com padrao=0 e continuam arquiváveis normalmente.
ALTER TABLE `financeiro_pessoal_contas`
  ADD COLUMN IF NOT EXISTS `padrao` TINYINT(1) NOT NULL DEFAULT 0 AFTER `arquivada`;
