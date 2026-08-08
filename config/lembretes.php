<?php

// Provedor de envio dos lembretes de agenda pro CLIENTE (WhatsApp/e-mail). Não afeta o
// lembrete interno pro técnico, que é sempre uma notificação in-app.
//
//   'fake' (padrão) — não envia nada de verdade, só grava em storage/logs/lembretes_fake.log.
//                      Seguro pra rodar em dev/homologação sem mandar mensagem real a cliente.
//   'app'           — usa o WhatsApp (Evolution API) e o e-mail (SMTP) já configurados da
//                      própria empresa em Configurações, via WhatsAppService/EmailService.
//
// Pra um fornecedor diferente, implemente App\Services\Lembretes\NotificacaoProviderInterface
// numa classe nova e adicione o case em App\Services\Lembretes\NotificacaoProviderFactory.
// Ver CLAUDE.md ("Lembretes de agenda") pra mais contexto.
return [
    'provider' => 'fake',
];
