<?php

namespace App\Enums;

/**
 * Tipo de um evento de agenda (coluna `agenda.tipo`). Rótulo/ícone/cores de
 * cada caso ficam em config/eventos_agenda.php, não aqui — este enum é só a
 * lista central de valores válidos, pra não ter string solta espalhada pelo
 * código (controllers, views, migrations) como acontecia antes.
 */
enum TipoEvento: string
{
    case OrdemServico = 'ordem_servico';
    case Coleta       = 'coleta';
    case Entrega      = 'entrega';
    case Financeiro   = 'financeiro';
    case Garantia     = 'garantia';
    case Pessoal      = 'pessoal';
    case Outro        = 'outro';

    /** Config visual deste tipo (rótulo, ícone, cores light/dark) — ver config/eventos_agenda.php. */
    public function config(): array
    {
        static $cfg = null;
        $cfg ??= require BASE_PATH . '/config/eventos_agenda.php';
        return $cfg['tipos'][$this->value];
    }

    public function rotulo(): string
    {
        return $this->config()['rotulo'];
    }

    public function icone(): string
    {
        return $this->config()['icone'];
    }
}
