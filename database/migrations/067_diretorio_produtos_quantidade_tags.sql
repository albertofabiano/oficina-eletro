-- Quantidade disponível (opcional, default 1 — a maioria dos produtos da vitrine do Diretório
-- é peça avulsa, não item de estoque com quantidade controlada) e tags (palavras-chave livres,
-- mesmo formato string separada por vírgula já usado em empresas.especialidades — reaproveita
-- o mesmo padrão de tag-input, só com paleta de cor diferente). Tags também alimentam o
-- JSON-LD/meta keywords da mini página do produto (SEO), ver DiretorioController::produto().
ALTER TABLE diretorio_produtos
  ADD COLUMN IF NOT EXISTS quantidade INT UNSIGNED NOT NULL DEFAULT 1 AFTER valor,
  ADD COLUMN IF NOT EXISTS tags VARCHAR(255) NULL AFTER descricao;
