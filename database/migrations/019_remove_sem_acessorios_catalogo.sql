-- "Sem acessórios" deixou de ser um item do catálogo (equip_acessorios) e virou
-- um chip nativo/fixo na tela — nunca duplica, nunca é excluído ou renomeado
-- por engano. Remove as linhas antigas (inclusive as duplicadas que a migração
-- 018 e o cadastro de empresas geravam). Não afeta nenhuma OS já salva: o texto
-- "acessorios" das ordens de serviço é uma coluna de texto livre, independente
-- dessas linhas do catálogo.
DELETE FROM equip_acessorios WHERE LOWER(TRIM(nome)) = 'sem acessórios';
