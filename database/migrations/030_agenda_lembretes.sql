-- Lembretes de agenda: lembretes internos (técnico) e lembrete opcional pro cliente
-- (WhatsApp/e-mail), mais fila de disparo com retentativa e log de envio.
--
-- Config fica na própria linha de `agenda` (por evento/ocorrência, igual a cliente_id/os_id):
--   - lembrete_tecnico_offsets: CSV de minutos antes do início (ex.: "0,15,60,1440" = na hora,
--     15min, 1h e 1 dia antes). NULL/vazio = sem lembrete interno.
--   - lembrete_cliente_ativo/canal/offset/mensagem: um único lembrete opcional pro cliente,
--     com modelo de mensagem editável (variáveis {{cliente}}/{{data}}/{{hora}}/{{os}}/{{endereco}}
--     resolvidas em App\Services\Lembretes\AgendaLembreteService::renderizarMensagem()).
-- OBS: já existia uma coluna `lembrete_minutos` (INT único, migration 001) que nunca foi
-- usada em código nenhum — decorativa, como `status`. As colunas novas abaixo a substituem
-- de fato; `lembrete_minutos` fica intocada (não é lida por este recurso).
--
-- `agenda_lembretes_fila` é fila E log ao mesmo tempo: cada linha é um disparo agendado
-- (status=pendente) que vira o próprio registro do envio quando processado (status=enviado/
-- falha, com enviado_em/destino/mensagem/ultimo_erro preenchidos). Ver
-- App\Services\Lembretes\AgendaLembreteService::reagendar()/processarFila() e
-- CLAUDE.md ("Lembretes de agenda") pra onde plugar um provedor real de WhatsApp/e-mail.

ALTER TABLE `agenda`
  ADD COLUMN `lembrete_tecnico_offsets` VARCHAR(50) DEFAULT NULL AFTER `recorrencia_excluida`,
  ADD COLUMN `lembrete_cliente_ativo` TINYINT(1) NOT NULL DEFAULT 0 AFTER `lembrete_tecnico_offsets`,
  ADD COLUMN `lembrete_cliente_canal` ENUM('whatsapp','email') DEFAULT NULL AFTER `lembrete_cliente_ativo`,
  ADD COLUMN `lembrete_cliente_offset` INT DEFAULT NULL AFTER `lembrete_cliente_canal`,
  ADD COLUMN `lembrete_cliente_mensagem` TEXT DEFAULT NULL AFTER `lembrete_cliente_offset`;

CREATE TABLE IF NOT EXISTS `agenda_lembretes_fila` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `empresa_id` INT UNSIGNED NOT NULL,
  `agenda_id` INT UNSIGNED NOT NULL,
  -- Ocorrência de uma série recorrente que este disparo pertence (mesmo valor de
  -- agenda.recorrencia_data_original); NULL para evento normal/já materializado.
  `ocorrencia_data` DATE DEFAULT NULL,
  `destinatario_tipo` ENUM('tecnico','cliente') NOT NULL,
  `canal` ENUM('interno','whatsapp','email') NOT NULL,
  -- Minutos antes do início que este disparo representa — junto com agenda_id/ocorrencia_data/
  -- destinatario_tipo, evita duplicar o mesmo disparo ao reagendar (ver uq_lembrete_disparo).
  `offset_minutos` INT NOT NULL,
  `disparar_em` DATETIME NOT NULL,
  `status` ENUM('pendente','enviado','falha','cancelado') NOT NULL DEFAULT 'pendente',
  `tentativas` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `max_tentativas` TINYINT UNSIGNED NOT NULL DEFAULT 3,
  `ultimo_erro` TEXT DEFAULT NULL,
  `enviado_em` DATETIME DEFAULT NULL,
  `destino` VARCHAR(180) DEFAULT NULL COMMENT 'telefone/e-mail (cliente) ou nome do canal interno',
  `mensagem` TEXT DEFAULT NULL COMMENT 'Mensagem já renderizada (variáveis resolvidas) no momento do disparo',
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_lembrete_disparo` (`agenda_id`, `ocorrencia_data`, `destinatario_tipo`, `offset_minutos`),
  INDEX `idx_lembrete_fila_pendentes` (`status`, `disparar_em`),
  FOREIGN KEY (`empresa_id`) REFERENCES `empresas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`agenda_id`) REFERENCES `agenda`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
