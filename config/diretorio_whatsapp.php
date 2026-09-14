<?php

// Disparo de convite via WhatsApp pro Diretório ("reivindique seu perfil" pra quem já tem
// ficha, "cadastre-se grátis" pra quem ainda não tem) — ver
// App\Services\Prospeccao\DisparoWhatsappDiretorioService e CLAUDE.md.
//
// Limite DIÁRIO ÚNICO, compartilhado pelos dois tipos de convite — os dois saem do MESMO
// número (instância `fixaos`, WhatsAppService::enviarTextoPlataforma()), então o risco é o
// mesmo pro número inteiro, não por campanha.
//
// Deliberadamente conservador, SEM rampa de subida (ao contrário do e-mail, que já tem
// histórico real de volume seguro testado): mandar WhatsApp em massa pra número que nunca
// teve contato com o FixaOS tem um risco mais sério que e-mail — a Meta pode BANIR/bloquear
// o número por reclamação de spam, e esse mesmo número (`fixaos`) é usado pra coisa real:
// redefinir senha por WhatsApp, avisos da plataforma. Perder ele não é só "menos alcance",
// é quebrar um fluxo de segurança de conta de cliente de verdade. Comece baixo, suba só
// depois de acompanhar reclamação/bloqueio por um tempo — não existe "cota documentada"
// como a do Brevo pra calibrar contra, é julgamento de risco puro.
return [
    'limite_diario' => 15,
];
