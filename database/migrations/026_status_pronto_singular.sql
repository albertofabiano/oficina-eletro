-- Volta o status nativo "pronto" de "Prontos" para "Pronto" (desfaz a 014) em todas as
-- empresas que ainda usam o nome padrão (quem já personalizou o nome não é afetado).
UPDATE os_status
SET nome = 'Pronto'
WHERE codigo = 'pronto' AND nome = 'Prontos' AND bloqueado = 1;
