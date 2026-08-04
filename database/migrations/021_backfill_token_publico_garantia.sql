-- Corrige OS de retorno em garantia (abrirGarantia) criadas sem token_publico,
-- por um bug em que esse fluxo nunca gerava o token do link de acompanhamento.
-- Backfill: gera um token único de 32 caracteres hex para toda OS que ainda
-- esteja sem token_publico.
UPDATE ordens_servico
SET token_publico = MD5(CONCAT(UUID(), '-', id, '-', RAND()))
WHERE token_publico IS NULL OR token_publico = '';
