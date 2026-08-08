-- Dá semântica aos eventos de agenda: novo conjunto de valores para `tipo`
-- (ver App\Enums\TipoEvento) e valores adicionais para `status`
-- (ver App\Enums\StatusEvento). Cores/ícones/rótulos ficam em
-- config/eventos_agenda.php — este arquivo só ajusta o schema.
--
-- Feito em 3 passos pra não perder dado nem quebrar linhas existentes:
--   1) alarga o ENUM de `tipo` pra um superconjunto (valores antigos + novos);
--   2) migra os valores antigos que não têm par direto no novo conjunto;
--   3) estreita o ENUM pro conjunto final.
-- `status` não precisa desse cuidado: todo valor antigo já existe no novo
-- conjunto, é só alargar direto.

-- Passo 1: superconjunto (antigos + novos) pra aceitar qualquer linha existente
ALTER TABLE `agenda` MODIFY COLUMN `tipo`
  ENUM('visita','coleta','entrega','reuniao','ligacao','os','outro',
       'ordem_servico','financeiro','garantia','pessoal')
  DEFAULT 'outro';

-- Passo 2: remapeia os valores antigos sem correspondência direta no novo conjunto
-- ('coleta' e 'entrega' já existem nos dois conjuntos, não precisam de UPDATE)
UPDATE `agenda` SET `tipo` = 'ordem_servico' WHERE `tipo` = 'os';
UPDATE `agenda` SET `tipo` = 'outro'         WHERE `tipo` IN ('visita', 'reuniao', 'ligacao');

-- Passo 3: conjunto final (ver App\Enums\TipoEvento)
ALTER TABLE `agenda` MODIFY COLUMN `tipo`
  ENUM('ordem_servico','coleta','entrega','financeiro','garantia','pessoal','outro')
  DEFAULT 'outro';

-- `status`: conjunto final já contém todos os valores antigos, alarga direto
-- (ver App\Enums\StatusEvento)
ALTER TABLE `agenda` MODIFY COLUMN `status`
  ENUM('agendado','confirmado','em_andamento','concluido','cancelado','atrasado')
  DEFAULT 'agendado';
