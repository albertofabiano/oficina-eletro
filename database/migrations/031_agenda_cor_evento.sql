-- Cor personalizada por evento (opcional) — sobrescreve a cor do Tipo (config/eventos_agenda.php)
-- só nesse evento. `agenda.cor` já existe desde 001_schema.sql, mas nunca foi lido por
-- nenhuma view até agora (era um campo do formulário sem efeito nenhum) — todo evento
-- existente tem '#0d6efd' gravado ali, que é só o valor DEFAULT antigo da coluna, nunca uma
-- escolha deliberada de ninguém (o campo não tinha efeito visível pra ninguém "escolher" com
-- intenção). Sem essa limpeza, ligar a leitura de `cor` faria todo evento do sistema virar
-- azul de uma vez.
UPDATE `agenda` SET `cor` = NULL WHERE `cor` = '#0d6efd';

-- Daqui pra frente, evento sem cor personalizada grava NULL (não mais '#0d6efd') — ver
-- agenda_evento_cor() em app/Views/agenda/_evento.php: NULL/vazio = usa a cor do Tipo.
ALTER TABLE `agenda` ALTER `cor` SET DEFAULT NULL;
