<?php

namespace App\Enums;

/**
 * Status de um evento de agenda (coluna `agenda.status`). Hoje o valor é
 * gravado como 'agendado' na criação e nunca muda depois (ver mapeamento da
 * tela de Agenda no CLAUDE.md) — este enum é só a lista central de valores
 * válidos + config visual; a lógica de transição de status fica pra depois.
 */
enum StatusEvento: string
{
    case Agendado    = 'agendado';
    case Confirmado  = 'confirmado';
    case EmAndamento = 'em_andamento';
    case Concluido   = 'concluido';
    case Cancelado   = 'cancelado';
    case Atrasado    = 'atrasado';

    /** Config visual deste status (rótulo, cores light/dark) — ver config/eventos_agenda.php. */
    public function config(): array
    {
        static $cfg = null;
        $cfg ??= require BASE_PATH . '/config/eventos_agenda.php';
        return $cfg['status'][$this->value];
    }

    public function rotulo(): string
    {
        return $this->config()['rotulo'];
    }
}
