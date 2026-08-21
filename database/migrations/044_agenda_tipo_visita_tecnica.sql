-- Novo tipo de evento de agenda "Visita Técnica" (ver App\Enums\TipoEvento e
-- config/eventos_agenda.php — este arquivo só ajusta o schema, rótulo/ícone/cor ficam lá).
ALTER TABLE `agenda` MODIFY COLUMN `tipo`
  ENUM('ordem_servico','coleta','entrega','financeiro','garantia','pessoal','outro','visita_tecnica')
  DEFAULT 'outro';
