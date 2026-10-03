-- Coordenadas aproximadas de cada região de visita (complementa a migration 071) — pedido do
-- usuário: mostrar num mapa, dentro de um modal, de onde vêm as visitas do perfil. ip-api.com
-- já devolve lat/lon no mesmo lote usado pra resolver cidade/uf (scripts/
-- resolver_geo_visitas_diretorio.php), então não precisa de uma segunda chamada externa — só
-- passou a pedir os campos extras e guardar aqui. Nullable porque linhas já resolvidas antes
-- desta migration (se o cron já rodou) ficam sem coordenada até a próxima visita daquela
-- cidade/empresa atualizar a linha (ON DUPLICATE KEY UPDATE já regrava lat/lng a cada acerto).
ALTER TABLE `diretorio_visitas_regiao`
  ADD COLUMN IF NOT EXISTS `lat` DECIMAL(10,7) NULL AFTER `total`,
  ADD COLUMN IF NOT EXISTS `lng` DECIMAL(10,7) NULL AFTER `lat`;
