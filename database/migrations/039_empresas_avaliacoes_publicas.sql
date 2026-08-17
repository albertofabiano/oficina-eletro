-- Liga/desliga a seção de Avaliações (resumo + lista + formulário de nova avaliação) na página
-- pública da empresa no Diretório — controlado em Empresa → Perfil Público. Default 1 (ligado)
-- pra não mudar o comportamento de perfis já publicados.
ALTER TABLE `empresas`
  ADD COLUMN `avaliacoes_publicas` TINYINT(1) NOT NULL DEFAULT 1;
