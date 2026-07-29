-- Guarda o lancamento financeiro criado ao marcar a comissao como paga, pra poder
-- desfazer/atualizar esse lancamento se a comissao for editada ou excluida depois.
ALTER TABLE fin_comissoes ADD COLUMN lancamento_id INT UNSIGNED NULL AFTER pago;
