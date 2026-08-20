<?php

// Disparo de e-mail de prospecção (convite pro diretório grátis) — ver
// MasterController::prospeccaoDisparar(), App\Services\Prospeccao\DisparoService e CLAUDE.md
// ("Disparo de e-mail de prospecção").
//
// RAMPA DE VOLUME: pedido do usuário pra chegar a 1.000/dia (a base de leads tem muito CNPJ de
// segmento errado/empresa inativa/nunca-fechada-mas-parada — só uma fração pequena é lead
// aproveitável, então 20/dia é pouco alcance útil) SEM pular direto pro número alto. Um salto
// brusco de volume, sem histórico de envio nesse patamar, costuma ser tratado como spam pelos
// provedores (Gmail/Outlook) — e isso prejudicaria também os e-mails REAIS do sistema
// (confirmação de cadastro, recibos), que usam o mesmo domínio/SMTP. `rampa` sobe em degraus
// maiores e mais frequentes que o "dobrar a cada poucos dias" original, chegando em 1.000/dia em
// ~2 semanas — App\Services\Prospeccao\DisparoService::limiteDiarioAtual() calcula o degrau do
// dia a partir de `rampa_inicio`. PARE de subir (ou volte um degrau, editando os valores abaixo)
// se notar taxa de bounce alta (e-mail inexistente/caixa cheia) ou reclamação de spam — não
// existe número "oficial" universal, o teto real depende da reputação do domínio/IP.
return [
    'rampa_inicio' => '2026-08-20',
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
    // ver DisparoService::limiteDiarioAtual().
    'limite_diario' => 20,

    // Acompanhamento pós-publicação do diretório (scripts/disparar_followup_diretorio.php):
    // quantos dias depois de a empresa publicar o perfil grátis (empresas.diretorio_publicado_em)
    // o convite pro sistema completo é enviado. Só uma vez por empresa (nunca reenvia).
    'followup_dias' => 5,
];
