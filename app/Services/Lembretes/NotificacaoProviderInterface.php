<?php

namespace App\Services\Lembretes;

/**
 * Ponto de plugue para o envio de lembretes ao CLIENTE (WhatsApp/e-mail) — o lembrete
 * INTERNO (técnico) não passa por aqui, é sempre uma notificação in-app via NotificacaoService.
 * Ver App\Services\Lembretes\NotificacaoProviderFactory e CLAUDE.md ("Lembretes de agenda")
 * pra saber qual implementação está ativa e como trocar.
 */
interface NotificacaoProviderInterface
{
    /**
     * Envia $mensagem para $destino (telefone ou e-mail) pelo canal indicado.
     * Deve lançar NotificacaoEnvioException em caso de falha — quem chama trata isso como uma
     * tentativa falha (registra o erro e reagenda, respeitando max_tentativas).
     */
    public function enviar(int $empresaId, string $canal, string $destino, string $mensagem): void;
}
