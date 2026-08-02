-- Categoria financeira padrão "Serviços" (receita), usada para categorizar
-- automaticamente a receita gerada no fechamento de OS. Cria a categoria para
-- toda empresa já cadastrada que ainda não a tenha (sem chave única em
-- fin_categorias, por isso o guard via NOT EXISTS) e faz o backfill dos
-- lançamentos de receita de OS já existentes que ficaram sem categoria.

INSERT INTO fin_categorias (empresa_id, tipo, nome, cor)
SELECT e.id, 'receita', 'Serviços', '#198754'
FROM empresas e
WHERE NOT EXISTS (
    SELECT 1 FROM fin_categorias fc
    WHERE fc.empresa_id = e.id AND fc.tipo = 'receita' AND fc.nome = 'Serviços'
);

UPDATE fin_lancamentos fl
JOIN fin_categorias fc ON fc.empresa_id = fl.empresa_id AND fc.tipo = 'receita' AND fc.nome = 'Serviços'
SET fl.categoria_id = fc.id
WHERE fl.tipo = 'receita' AND fl.os_id IS NOT NULL AND fl.categoria_id IS NULL;
