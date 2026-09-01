-- "Posição" do banner do Diretório deixa de ser um número arbitrário 1-5 (que não controlava
-- lugar visual nenhum — só limitava quantos anunciantes podiam estar ativos ao mesmo tempo,
-- todos sorteados no mesmo espaço único) e passa a ser um slug de posição real (busca_topo,
-- busca_lateral, perfil, cidade — ver app/Helpers/functions.php::diretorio_banner_posicoes()),
-- cada uma exibida num lugar físico próprio do site.
ALTER TABLE `diretorio_planos`  MODIFY COLUMN `posicao_banner` VARCHAR(30) NULL;
ALTER TABLE `diretorio_banners` MODIFY COLUMN `posicao`        VARCHAR(30) NOT NULL;
