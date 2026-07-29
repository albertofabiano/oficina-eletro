-- A tela de Nova Comissao sempre tratou "OS relacionada" como opcional (dá pra lançar comissão
-- sem vincular a uma OS específica), mas a coluna nasceu NOT NULL — salvar sem OS quebrava com
-- erro de integridade. Corrige pra aceitar NULL, que é o que o app já espera.
ALTER TABLE fin_comissoes MODIFY COLUMN os_id INT UNSIGNED NULL;
