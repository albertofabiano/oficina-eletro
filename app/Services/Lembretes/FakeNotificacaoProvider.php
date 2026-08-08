<?php

namespace App\Services\Lembretes;

/**
 * Implementação PADRÃO (nenhum fornecedor real configurado): não manda nada de verdade, só
 * grava uma linha em storage/logs/lembretes_fake.log — dá pra "ver" o envio em dev/homologação
 * sem depender de WhatsApp/SMTP configurados. Nunca lança exceção (sempre "sucesso").
 *
 * Pra ligar o envio de verdade, troque 'provider' em config/lembretes.php — ver
 * App\Services\Lembretes\NotificacaoProviderFactory e CLAUDE.md ("Lembretes de agenda").
 */
class FakeNotificacaoProvider implements NotificacaoProviderInterface
{
    public function enviar(int $empresaId, string $canal, string $destino, string $mensagem): void
    {
        $cfg = require BASE_PATH . '/config/app.php';
        $dir = $cfg['log_path'];
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        $linha = sprintf(
            "[%s] empresa=%d FAKE %s -> %s: %s\n",
            date('Y-m-d H:i:s'), $empresaId, strtoupper($canal), $destino, str_replace("\n", ' ', $mensagem)
        );
        @file_put_contents($dir . '/lembretes_fake.log', $linha, FILE_APPEND | LOCK_EX);
    }
}
