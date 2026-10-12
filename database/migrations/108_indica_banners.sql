-- Banners promocionais na sidebar da página de produto do Fixa Indica — hoje só do próprio
-- FixaOS (planos, Carteira Fixa, convite pro Diretório grátis); exibidos em lista estática,
-- sem rotação/randomização (fica pra quando entrar algo tipo AdSense, fora de escopo agora —
-- `posicao` já deixa a tabela pronta pra outras áreas do site além da página de produto, se
-- precisar um dia). Admin controla tudo: título, texto, link, cor, ordem, liga/desliga.
CREATE TABLE IF NOT EXISTS `indica_banners` (
  `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `titulo`    VARCHAR(100) NOT NULL,
  `descricao` VARCHAR(200) NULL,
  `link`      VARCHAR(500) NOT NULL,
  `cor`       VARCHAR(7) NOT NULL DEFAULT '#7c3aed',
  `posicao`   VARCHAR(30) NOT NULL DEFAULT 'sidebar_produto',
  `ordem`     INT NOT NULL DEFAULT 0,
  `ativo`     TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `indica_banners` (titulo, descricao, link, cor, ordem) VALUES
('Sistema completo pra assistência técnica', 'Ordens de serviço, financeiro, estoque e PDV — tudo num só lugar. Veja os planos.', 'https://fixaos.com.br/planos', '#7c3aed', 1),
('Carteira Fixa', 'Controle financeiro pessoal e empresarial, simples e rápido.', 'https://carteirafixa.fixaos.com.br/', '#f97316', 2),
('Cadastre sua assistência grátis', 'Apareça no Diretório FixaOS pra quem está procurando assistência técnica na sua região.', 'https://fixaos.com.br/diretorio/cadastrar', '#16a34a', 3);
