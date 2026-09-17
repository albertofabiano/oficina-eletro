-- Vitrine do Diretório: até 10 anúncios do Marketplace, marcados pela própria empresa, também
-- aparecem no perfil público dela em /assistencias/{slug} — benefício de plano pago ativo
-- (mesmo critério de perfil_diretorio_completo(), já usado por Destaque/Visitas), sem consumir
-- crédito do Marketplace. Continua contando normalmente na busca geral de /pecas — não é
-- exclusivo de um lugar só, soma tráfego dos dois lados.
ALTER TABLE marketplace_anuncios
  ADD COLUMN IF NOT EXISTS exibir_diretorio TINYINT(1) NOT NULL DEFAULT 0 AFTER status;
