-- Início/fim de vigência das contas recorrentes (pedido do usuário: "coloque início e fim da
-- conta recorrente, sem fim em branco"). `data_inicio` trava a geração ANTES dessa data — uma
-- recorrência cadastrada hoje não inventa ocorrência passada por engano (mesmo princípio que já
-- valia antes, só que agora explícito/editável, ver RecorrenteService::gerarPendentes(), que já
-- nunca gerava pro passado via `$vencimento < $hoje`). `data_fim` é opcional: NULL (deixado em
-- branco na UI) significa "sem fim, repete pra sempre" — mesmo comportamento de toda recorrência
-- já cadastrada antes desta migration, que nasce com os dois campos NULL.
ALTER TABLE `financeiro_pessoal_recorrentes`
  ADD COLUMN IF NOT EXISTS `data_inicio` DATE NULL AFTER `dia_vencimento`,
  ADD COLUMN IF NOT EXISTS `data_fim` DATE NULL AFTER `data_inicio`;
