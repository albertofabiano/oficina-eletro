-- Renomeia o status nativo "pronto" de "Pronto" para "Prontos" em todas as empresas
-- que ainda usam o nome padrão (empresas que já personalizaram o nome não são afetadas).

UPDATE os_status
SET nome = 'Prontos'
WHERE codigo = 'pronto' AND nome = 'Pronto' AND bloqueado = 1;
