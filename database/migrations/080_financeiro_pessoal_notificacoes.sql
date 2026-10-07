-- Sino de notificação da Agenda do Financeiro Pessoal (pedido do usuário: "sino de
-- notificação com alerta sonoro e tempo de exibição, podendo ser habilitado e desabilitado
-- em configurações"). Confirmado com o usuário: alerta quando um evento da Agenda chega no
-- horário (mesmo instante do `data_hora`, igual o alerta sonoro já existente no sistema
-- principal da FixaOS — "no instante 0, vencimento"), e "tempo de exibição" é quanto tempo o
-- popup de aviso fica na tela antes de sumir sozinho.
--
-- `lido_em` fica na própria linha do evento (igual `agenda.ultimo_alerta_pendente_em` no
-- sistema principal) — não precisa de tabela de fila/notificação separada: um evento sem
-- `lido_em` é uma notificação pendente; marcar como lido grava a data. Preferências de
-- som/tempo de exibição ficam em `usuarios` (prefixo `fp_`, pra não colidir com nenhuma
-- preferência de notificação do sistema principal que `usuarios` já tenha ou venha a ter) —
-- é configuração POR USUÁRIO, igual o resto do Financeiro Pessoal (avatar, categorias).

ALTER TABLE `financeiro_pessoal_eventos`
  ADD COLUMN `lido_em` DATETIME NULL AFTER `data_hora`;

ALTER TABLE `usuarios`
  ADD COLUMN `fp_notif_som` TINYINT(1) NOT NULL DEFAULT 1 AFTER `avatar`,
  ADD COLUMN `fp_notif_tempo_exibicao` SMALLINT UNSIGNED NOT NULL DEFAULT 6 AFTER `fp_notif_som`;
