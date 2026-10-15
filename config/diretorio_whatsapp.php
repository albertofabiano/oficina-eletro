<?php

// Disparo de convite via WhatsApp pro Diretório ("reivindique seu perfil" pra quem já tem
// ficha, "cadastre-se grátis" pra quem ainda não tem, lista manual curada pelo Master) — ver
// App\Services\Prospeccao\DisparoWhatsappDiretorioService e CLAUDE.md.
//
// Mesmo número pros 3 tipos (instância `fixaos`, WhatsAppService::enviarTextoPlataforma()),
// então o risco de bloqueio é sempre do número inteiro — mas DUAS cotas diárias separadas,
// não uma só:
//
// - 'limite_diario' — reivindicar() + cadastrar(), as duas frentes de CNPJ FRIO (leads sem
//   nenhum sinal de interesse real, só bateram palavra do ramo na base da Receita).
// - 'limite_diario_manual' — Lista Manual (diretorio_convites_manuais): lead curado à mão
//   pelo Master, incluindo a leitura de banner/fachada por IA (foto real de negócio ativo,
//   nome+WhatsApp conferidos por humano antes de entrar na lista) — risco bem menor de
//   "flecha em quem não se interessa" que o CNPJ frio, por isso tem cota própria, separada.
//
// PAUSADO em 2026-10-15 (pedido do usuário): "vamos parar de enviar email e whatsapp sem saber
// a direção" — na época as 3 frentes compartilhavam 1 único limite (cobertas juntas). Em
// seguida (mesmo dia) o usuário validou a Lista Manual com um envio de teste de verdade e
// pediu pra ela voltar a ter cota própria, já que deixou de ser "sem saber a direção" — as
// duas frentes de CNPJ frio (reivindicar/cadastrar) continuam pausadas.
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
    'limite_diario'        => 0,
    'limite_diario_manual' => 15,
];
