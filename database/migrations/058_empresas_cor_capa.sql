-- Cor de fundo do banner/título do perfil público do Diretório, escolhida pela própria
-- empresa — substitui a antiga "foto de capa" (upload de imagem), removida a pedido do
-- usuário. `foto_capa` continua existindo na tabela (não removida — dado histórico,
-- reversível), só deixou de ser lida/gravada por qualquer código a partir de agora.
ALTER TABLE empresas ADD COLUMN IF NOT EXISTS cor_capa VARCHAR(7) NULL AFTER foto_capa;
