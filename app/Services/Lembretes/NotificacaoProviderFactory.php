<?php

namespace App\Services\Lembretes;

/**
 * Ponto ÚNICO de troca de provedor dos lembretes de cliente (WhatsApp/e-mail) — ver
 * CLAUDE.md ("Lembretes de agenda") pra a explicação completa. Controlado por
 * config/lembretes.php:
 *   - 'fake' (padrão): FakeNotificacaoProvider, não envia nada de verdade, só loga.
 *   - 'app': AppNotificacaoProvider, reaproveita WhatsAppService/EmailService já existentes.
 *   - qualquer outro valor: implemente NotificacaoProviderInterface numa classe nova e
 *     adicione o case abaixo.
 */
class NotificacaoProviderFactory
{
    public static function criar(): NotificacaoProviderInterface
    {
        $arquivo = BASE_PATH . '/config/lembretes.php';
        $cfg = is_file($arquivo) ? require $arquivo : [];

        return match ($cfg['provider'] ?? 'fake') {
            'app'   => new AppNotificacaoProvider(),
            default => new FakeNotificacaoProvider(),
        };
    }
}
