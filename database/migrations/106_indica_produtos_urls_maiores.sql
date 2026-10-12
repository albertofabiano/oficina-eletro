-- Corrige erro 500 real em produção: URL do Mercado Livre colada no cadastro de produto
-- (com os parâmetros de rastreamento que o próprio ML embute, ex. busca/posição/variação)
-- passava de 500 caracteres e o INSERT quebrava com "Data too long for column 'link_ml'"
-- (PDOException não capturada em AdminController::produtoSalvar()). link_afiliado/imagem_url
-- têm o mesmo risco (também vêm de URL colada/gerada), alargados junto por precaução.
ALTER TABLE `indica_produtos`
  MODIFY COLUMN `link_ml`       TEXT NULL,
  MODIFY COLUMN `link_afiliado` TEXT NULL,
  MODIFY COLUMN `imagem_url`    TEXT NULL;
