-- Terceiro tipo de conta: 'fixa' — quem se cadastra direto pro Carteira Fixa standalone
-- (financeiro pessoal vendido à parte, "cadastro próprio e simples" já decidido com o usuário),
-- sem nunca ter sido cliente de assistência técnica. Cria uma empresa "casca" por baixo (mesmo
-- motivo de 'diretorio': `usuarios.empresa_id` é NOT NULL, todo usuário precisa de uma linha em
-- `empresas`), mas com acesso restrito só a /financeiro-pessoal (ver Auth::soFixa(),
-- AuthMiddleware) — nunca aparece no Diretório, nunca vira "cliente de assistência técnica" de
-- verdade. Mesmo padrão já usado quando 'diretorio' foi adicionado a este ENUM.
ALTER TABLE `empresas`
  MODIFY COLUMN `tipo_conta` ENUM('completo','diretorio','fixa') NOT NULL DEFAULT 'completo';
