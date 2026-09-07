<?php

// Disparo de e-mail "reivindique seu perfil" pras empresas que já têm ficha no diretório
// (tabela diretorio_leads_email, extraída de `empresas` por scripts/extrair_emails_diretorio.php)
// — ver MasterController::diretorioEmails*() , App\Services\Prospeccao\DisparoDiretorioService
// e CLAUDE.md ("Disparo de e-mail pra captação de clientes do diretório").
//
// Config PRÓPRIA, separada de config/prospeccao_email.php de propósito — é outra campanha, pra
// outro público (quem já tem ficha publicada, não lead frio sem cadastro nenhum), com seu
// próprio limite diário/rampa.
//
// REBAIXADO em 2026-09-07 (mesmo incidente documentado em config/prospeccao_email.php): esta
// rampa (chegou a 1.000/dia) somada à do arquivo irmão (também 1.000/dia), na MESMA conta Brevo
// que manda os e-mails REAIS do sistema, estourou a cota do plano (300/dia + fila de retry de
// até 1.000) — a fila encheu e passou a descartar e-mails do dia sem erro nenhum, risco real
// pra e-mail de redefinir senha/confirmação de cadastro. Voltou a um valor fixo bem abaixo da
// cota, sem rampa de subida — juntos, os dois arquivos somam bem menos que 300/dia, deixando
// margem real pro e-mail transacional. Só suba de novo depois de resolver a causa raiz (plano
// pago com cota maior, e/ou conta/domínio separado só pra transacional).
return [
    'rampa_inicio' => '2026-09-07',
    'rampa' => [
        0 => 40,
    ],
    // Usado só se 'rampa'/'rampa_inicio' faltarem (compatibilidade) — nunca lido diretamente,
    // ver DisparoDiretorioService::limiteDiarioAtual().
    'limite_diario' => 40,
];
