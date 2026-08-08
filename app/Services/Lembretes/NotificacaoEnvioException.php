<?php

namespace App\Services\Lembretes;

/** Falha ao enviar um lembrete pelo provedor ativo — mensagem vira `ultimo_erro` na fila. */
class NotificacaoEnvioException extends \RuntimeException
{
}
