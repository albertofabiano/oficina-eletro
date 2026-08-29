-- Simplificação a pedido do usuário (ver CLAUDE.md "Financeiro → Agenda: ligar/desligar por
-- lançamento..."): a cor da etiqueta na Agenda deixou de ser escolhida manualmente (color
-- picker por lançamento, migration 048) e passou a ser automática por tipo — verde pra
-- receita, vermelho pra despesa (`FinanceiroController::corAgendaPorTipo()`), a mesma paleta já
-- usada em financeiro/categorias.php. `cor_agenda` não é mais lida/gravada por código nenhum.
ALTER TABLE `fin_lancamentos` DROP COLUMN IF EXISTS `cor_agenda`;
