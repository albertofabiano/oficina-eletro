-- Agenda de eventos do Financeiro Pessoal (pedido do usuário: "é para ser uma agenda de
-- eventos", substituindo a visualização de lançamentos por dia que a tela de Calendário tinha
-- antes). Escopado por `usuario_id`, igual `financeiro_pessoal_lancamentos`/`_categorias` —
-- mesma lógica de "pessoal, não da empresa" já documentada na migration 075. Deliberadamente
-- simples (só título + data/hora, confirmado com o usuário) — sem descrição, lembrete ou
-- vínculo com lançamento nesta rodada.

CREATE TABLE IF NOT EXISTS `financeiro_pessoal_eventos` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL,
  `titulo`     VARCHAR(150) NOT NULL,
  `data_hora`  DATETIME NOT NULL,
  `criado_em`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_usuario_data` (`usuario_id`, `data_hora`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
