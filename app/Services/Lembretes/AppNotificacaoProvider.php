<?php

namespace App\Services\Lembretes;

use App\Services\EmailService;
use App\Services\WhatsAppService;

/**
 * Provedor real que reaproveita as integrações que a empresa JÁ pode ter configurado no
 * FixaOS — WhatsAppService (Evolution API, por empresa) e EmailService (SMTP) — em vez de
 * inventar um fornecedor novo só pra lembrete. É o que 'app' liga em config/lembretes.php.
 *
 * Pra um fornecedor DIFERENTE desses (ex.: Twilio, um SMTP dedicado só pra lembretes), crie
 * outra classe implementando NotificacaoProviderInterface e aponte pra ela em
 * NotificacaoProviderFactory::criar() — não precisa mexer aqui.
 */
class AppNotificacaoProvider implements NotificacaoProviderInterface
{
    public function enviar(int $empresaId, string $canal, string $destino, string $mensagem): void
    {
        $ok = match ($canal) {
            'whatsapp' => WhatsAppService::enviarTexto($empresaId, $destino, $mensagem),
            'email'    => EmailService::send($destino, $destino, 'Lembrete de agendamento', nl2br(e($mensagem))),
            default    => throw new NotificacaoEnvioException("Canal não suportado: {$canal}"),
        };

        if (!$ok) {
            throw new NotificacaoEnvioException(
                $canal === 'whatsapp'
                    ? 'Falha ao enviar pelo WhatsApp da empresa (desconectado ou número inválido).'
                    : 'Falha ao enviar e-mail (SMTP não configurado ou endereço inválido).'
            );
        }
    }
}
