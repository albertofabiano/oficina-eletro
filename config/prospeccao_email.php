<?php

// Disparo de e-mail de prospecção (convite pro diretório grátis) — ver
// MasterController::prospeccaoDisparar(), App\Services\Prospeccao\DisparoService e CLAUDE.md
// ("Disparo de e-mail de prospecção").
//
// REBAIXADO em 2026-09-07 (incidente real): a rampa original (pedido do usuário, subindo até
// 1.000/dia em ~2 semanas) ignorava um teto que não é sobre reputação de domínio — é uma cota
// DURA do plano gratuito do Brevo (300 e-mails/dia + fila de retry de até 1.000; uma vez a fila
// cheia, e-mails do dia simplesmente NÃO são entregues, sem erro nenhum aparecer pro sistema).
// Somando esta rampa (chegou a 1.000/dia) com a de config/diretorio_leads_email.php (também
// 1.000/dia) na MESMA conta/domínio que manda os e-mails REAIS do sistema (redefinir senha,
// confirmação de cadastro, recibo), a conta estourou a fila — risco real de um cliente pedir
// "esqueci minha senha" e o e-mail nunca chegar. Achado através de um teste manual mandado pra
// e-mail pessoal (Brevo mandou aviso de "atingiu seus limites de envio").
//
// Voltou a um valor fixo BEM abaixo da cota de 300/dia do plano — de propósito, sem rampa de
// subida (não é o caso de "começar baixo pra construir reputação aos poucos": aqui é reduzir
// imediatamente pra parar de competir com e-mail transacional pela mesma cota). Só suba de novo
// depois de resolver a causa raiz (plano pago com cota maior, e/ou uma conta/domínio separado
// só pra e-mail transacional, nunca dividindo cota com disparo em massa).
return [
    'rampa_inicio' => '2026-09-07',
    'rampa' => [
        0 => 80,
    ],
    // Usado só se 'rampa'/'rampa_inicio' faltarem (compatibilidade) — nunca lido diretamente,
    // ver DisparoService::limiteDiarioAtual().
    'limite_diario' => 80,

    // Acompanhamento pós-publicação do diretório (scripts/disparar_followup_diretorio.php):
    // quantos dias depois de a empresa publicar o perfil grátis (empresas.diretorio_publicado_em)
    // o convite pro sistema completo é enviado. Só uma vez por empresa (nunca reenvia).
    'followup_dias' => 5,
];
