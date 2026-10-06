-- Financeiro pessoal — Categorias viram CRUD de verdade (eram um PHP const fixo em
-- FinanceiroPessoalController::CATEGORIAS, 7 categorias iguais pra todo mundo, sem jeito de
-- criar/editar/excluir). Pedido do usuário: um menu de Categorias na barra lateral, com um
-- CRUD em lista.
--
-- `chave` continua sendo o valor gravado em `financeiro_pessoal_lancamentos.categoria`
-- (string, não FK por id) — de propósito, pra todo lançamento já existente (que guarda
-- 'alimentacao'/'transporte'/etc.) continuar batendo sem precisar de nenhuma migração de
-- dado: o primeiro acesso de cada usuário semeia as 7 categorias padrão com as MESMAS chaves
-- que o const antigo já usava (ver FinanceiroPessoalController::categoriasDoUsuario()).
--
-- `cor` aceita tanto `var(--cat-x)` (as 7 padrão, adaptam sozinhas ao tema claro/escuro) quanto
-- um hex literal `#rrggbb` (categoria nova criada pelo usuário, cor fixa escolhida por ele —
-- mesmo padrão já usado em empresas.cor_capa).
CREATE TABLE IF NOT EXISTS `financeiro_pessoal_categorias` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL,
  `chave`      VARCHAR(40) NOT NULL,
  `nome`       VARCHAR(60) NOT NULL,
  `cor`        VARCHAR(30) NOT NULL DEFAULT '#7A6A88',
  `ativo`      TINYINT(1) NOT NULL DEFAULT 1,
  `posicao`    INT NOT NULL DEFAULT 0,
  `criado_em`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_usuario_chave` (`usuario_id`, `chave`),
  KEY `idx_usuario` (`usuario_id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
