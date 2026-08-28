<?php

// Disparo de e-mail "reivindique seu perfil" pras empresas que já têm ficha no diretório
// (tabela diretorio_leads_email, extraída de `empresas` por scripts/extrair_emails_diretorio.php)
// — ver MasterController::diretorioEmails*() , App\Services\Prospeccao\DisparoDiretorioService
// e CLAUDE.md ("Disparo de e-mail pra captação de clientes do diretório").
//
// Config PRÓPRIA, separada de config/prospeccao_email.php de propósito — é outra campanha, pra
// outro público (quem já tem ficha publicada, não lead frio sem cadastro nenhum), com seu
// próprio limite diário/rampa. Mesmo racional de reputação de envio do arquivo irmão: começa
// conservador e sobe aos poucos, nunca pulando direto pro número alto — um salto brusco de
// volume sem histórico nesse patamar é tratado como spam pelos provedores (Gmail/Outlook), o
// que prejudicaria também os e-mails REAIS do sistema (confirmação de cadastro, recibos), já
// que usam o mesmo domínio/SMTP. PARE de subir (ou volte um degrau) se notar taxa de bounce
// alta ou reclamação de spam.
return [
    'rampa_inicio' => '2026-08-28',
    'rampa' => [
        0  => 20,
        2  => 60,
        4  => 150,
        6  => 300,
        9  => 500,
        12 => 750,
        15 => 1000,
    ],
    // Usado só se 'rampa'/'rampa_inicio' faltarem (compatibilidade) — nunca lido diretamente,
    // ver DisparoDiretorioService::limiteDiarioAtual().
    'limite_diario' => 20,
];
