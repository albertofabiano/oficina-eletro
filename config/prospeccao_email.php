<?php

// Disparo de e-mail de prospecção (convite pro diretório grátis) — ver
// MasterController::prospeccaoDisparar() e CLAUDE.md ("Disparo de e-mail de prospecção").
//
// limite_diario: comece baixo e suba aos poucos. 20/dia é seguro pra praticamente qualquer
// domínio, mesmo sem histórico de envio. Dá pra ir dobrando a cada poucos dias (20 → 40 → 80…)
// enquanto a taxa de bounce continuar baixa (sem erro de "unknown user"/caixa cheia) e não
// houver reclamação de spam — não existe um número "oficial" universal, o teto real depende da
// reputação do domínio/IP e das regras do provedor SMTP configurado em config/email.php.
// Cair de propósito pra um número mais baixo é sempre reversível editando aqui; SUBIR sem
// monitorar as duas métricas acima é o que arrisca o domínio (usado também pros e-mails de
// verdade do sistema: confirmação de cadastro, recibos etc.).
return [
    'limite_diario' => 20,

    // Acompanhamento pós-publicação do diretório (scripts/disparar_followup_diretorio.php):
    // quantos dias depois de a empresa publicar o perfil grátis (empresas.diretorio_publicado_em)
    // o convite pro sistema completo é enviado. Só uma vez por empresa (nunca reenvia).
    'followup_dias' => 5,
];
