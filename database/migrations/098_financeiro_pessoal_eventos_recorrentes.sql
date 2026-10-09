-- Eventos recorrentes da Agenda do Carteira Fixa (pedido do usuário: "faça o mesmo [de Contas
-- recorrentes] pra editar evento" — mesmo padrão de molde mensal + Início/Repetir por, só que
-- pra um compromisso sem dinheiro envolvido, ex.: "Consulta médica" todo dia 10, por 6 meses).
-- Mesmo princípio de `financeiro_pessoal_recorrentes` (ver migration 092/097): um MOLDE, nunca
-- um evento em si — a cada visita ao módulo, EventoRecorrenteService::gerarPendentes() garante
-- que os eventos de verdade (`financeiro_pessoal_eventos`, `recorrente_id` apontando de volta)
-- já existem pros meses que cabem na janela de vigência.
--
-- Sem os campos de dinheiro de `financeiro_pessoal_recorrentes` (tipo/categoria/valor/conta) —
-- evento é só um lembrete, não uma transação. `hora` fica separada de `dia_mes` porque o evento
-- de verdade (`data_hora`, DATETIME) precisa das duas coisas combinadas a cada geração mensal.
CREATE TABLE IF NOT EXISTS `financeiro_pessoal_eventos_recorrentes` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id`  INT UNSIGNED NOT NULL,
  `perfil_id`   INT UNSIGNED NOT NULL,
  `titulo`      VARCHAR(150) NOT NULL,
  `dia_mes`     TINYINT UNSIGNED NOT NULL,
  `hora`        TIME NOT NULL DEFAULT '08:00:00',
  `data_inicio` DATE NULL,
  `data_fim`    DATE NULL,
  `ativo`       TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_usuario_perfil` (`usuario_id`, `perfil_id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`perfil_id`) REFERENCES `financeiro_pessoal_perfis`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- `financeiro_pessoal_eventos` ganha o vínculo de volta — mesmo padrão de
-- `financeiro_pessoal_lancamentos.recorrente_id` (migration 093), ON DELETE SET NULL: excluir o
-- molde não apaga os eventos já gerados, só solta o vínculo.
ALTER TABLE `financeiro_pessoal_eventos`
  ADD COLUMN IF NOT EXISTS `recorrente_id` INT UNSIGNED NULL AFTER `lancamento_id`;

-- Statement separado (mesma cautela já documentada em 084/085/092 deste módulo — se a migration
-- rodar de novo por engano, só esta linha falha, "Duplicate foreign key", sem travar o resto).
ALTER TABLE `financeiro_pessoal_eventos`
  ADD KEY `idx_evt_recorrente` (`recorrente_id`),
  ADD CONSTRAINT `fk_fpevt_recorrente` FOREIGN KEY (`recorrente_id`) REFERENCES `financeiro_pessoal_eventos_recorrentes`(`id`) ON DELETE SET NULL;
