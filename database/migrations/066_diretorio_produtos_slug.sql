-- URL amigável pra mini página de cada produto do Diretório (/produto-diretorio/{slug}), mesmo
-- padrão já usado por marketplace_anuncios.slug — gerado em PHP no cadastro/edição
-- (DiretorioProdutosController), nunca no banco. Sem UNIQUE de propósito: a dedupe (sufixo
-- -2, -3...) já é garantida em código, igual MarketplaceController::gerarSlug() — produtos
-- cadastrados antes desta migration ficam com slug NULL até a próxima edição, que preenche
-- sozinho; até lá, a rota continua funcionando pelo id numérico (fallback com redirect 301).
ALTER TABLE diretorio_produtos
  ADD COLUMN IF NOT EXISTS slug VARCHAR(160) NULL AFTER titulo,
  ADD INDEX IF NOT EXISTS idx_slug (slug);
