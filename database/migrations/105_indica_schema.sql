-- Fixa Indica (indica.fixaos.com.br) — site de afiliado do Mercado Livre, produto próprio do
-- ecossistema FixaOS (ver fixa-indica-spec.md). Deliberadamente SEM `empresa_id`/`usuario_id`
-- — ao contrário de praticamente toda outra tabela do sistema, não é multi-tenant: é um site
-- único, administrado só pelo dono (master admin), sem conceito de "empresa cliente" aqui.
--
-- Fase 1 (schema completo, ainda sem integração real com a API do Mercado Livre nem IA —
-- ver CLAUDE.md/plano, isso entra nas fases seguintes).

CREATE TABLE IF NOT EXISTS `indica_categorias` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nome`       VARCHAR(80) NOT NULL,
  `slug`       VARCHAR(100) NOT NULL UNIQUE,
  `icone`      VARCHAR(40) NULL,
  `texto_seo`  TEXT NULL,
  `ordem`      INT UNSIGNED NOT NULL DEFAULT 0,
  `ativa`      TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `preco`/`preco_atualizado_em` — a rotina diária (Fase 3) sobrescreve os dois; sem a rotina
-- rodando ainda (Fase 1), fica só o valor digitado manualmente no cadastro.
-- `link_ml` é o anúncio original (usado pra rotina de atualização de preço via API oficial);
-- `link_afiliado` é o que de fato sai no botão "Ver no Mercado Livre" — os dois são campos
-- deliberadamente separados (o afiliado é gerado manualmente pelo dono no painel de afiliados
-- do ML, nunca por ferramenta não-oficial, ver spec).
-- `selos` (JSON: ["frete_gratis","mais_vendido","oferta_do_dia"]) e `vendedor_parceiro`
-- (comissão extra) são só flags de exibição, sem lógica de cálculo nenhuma ainda.
CREATE TABLE IF NOT EXISTS `indica_produtos` (
  `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `categoria_id`          INT UNSIGNED NULL,
  `nome`                  VARCHAR(150) NOT NULL,
  `slug`                  VARCHAR(180) NOT NULL UNIQUE,
  `especificacao_curta`   VARCHAR(200) NULL,
  `descricao`             TEXT NULL,
  `preco`                 DECIMAL(10,2) NULL,
  `preco_atualizado_em`   DATETIME NULL,
  `disponivel`            TINYINT(1) NOT NULL DEFAULT 1,
  `link_ml`               VARCHAR(500) NULL,
  `link_afiliado`         VARCHAR(500) NULL,
  `imagem_url`            VARCHAR(500) NULL,
  `selos`                 JSON NULL,
  `destaque_home`         TINYINT(1) NOT NULL DEFAULT 0,
  `vendedor_parceiro`     TINYINT(1) NOT NULL DEFAULT 0,
  `meta_title`            VARCHAR(70) NULL,
  `meta_description`      VARCHAR(160) NULL,
  `ativo`                 TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em`             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_categoria` (`categoria_id`),
  CONSTRAINT `fk_indicaprod_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `indica_categorias`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `indica_guias` (
  `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `titulo`            VARCHAR(150) NOT NULL,
  `slug`              VARCHAR(180) NOT NULL UNIQUE,
  `status`            ENUM('rascunho','publicado') NOT NULL DEFAULT 'rascunho',
  `meta_title`        VARCHAR(70) NULL,
  `meta_description`  VARCHAR(160) NULL,
  `criado_em`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `publicado_em`      DATETIME NULL,
  `atualizado_em`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Editor por blocos (spec: "título de seção, parágrafo, card de produto, aviso") — `conteudo`
-- guarda o texto (tipo titulo/paragrafo/aviso) ou `{"produto_id": N}` (tipo produto), sempre
-- JSON pra não precisar de uma tabela por tipo de bloco.
CREATE TABLE IF NOT EXISTS `indica_guia_blocos` (
  `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `guia_id`   INT UNSIGNED NOT NULL,
  `ordem`     INT UNSIGNED NOT NULL DEFAULT 0,
  `tipo`      ENUM('titulo','paragrafo','produto','aviso') NOT NULL,
  `conteudo`  JSON NOT NULL,
  KEY `idx_guia` (`guia_id`, `ordem`),
  CONSTRAINT `fk_indicablocos_guia` FOREIGN KEY (`guia_id`) REFERENCES `indica_guias`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Clique registrado em /ir/{slug}, sem dado pessoal (LGPD, spec explícita): produto, de onde
-- veio (ex. 'home'/'categoria'/'produto'/'guia'), quando, e um resumo curto do user-agent (só
-- "mobile"/"desktop" + navegador, nunca a string completa/fingerprintável).
CREATE TABLE IF NOT EXISTS `indica_cliques` (
  `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `produto_id`           INT UNSIGNED NOT NULL,
  `origem`               VARCHAR(40) NOT NULL DEFAULT 'produto',
  `user_agent_resumido`  VARCHAR(60) NULL,
  `criado_em`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_produto_data` (`produto_id`, `criado_em`),
  CONSTRAINT `fk_indicaclique_produto` FOREIGN KEY (`produto_id`) REFERENCES `indica_produtos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Config chave/valor (aviso de afiliado, link do WhatsApp, faixa FixaOS liga/desliga) — mesmo
-- padrão já usado em `configuracoes`/`sistema_config` no resto do projeto, só isolado numa
-- tabela própria por não ter empresa_id pra filtrar.
CREATE TABLE IF NOT EXISTS `indica_config` (
  `chave`  VARCHAR(60) NOT NULL PRIMARY KEY,
  `valor`  TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
