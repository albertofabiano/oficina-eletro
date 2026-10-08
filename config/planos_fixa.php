<?php
/*
 * Planos do Fixa STANDALONE (financeiro pessoal + agenda vendido à parte, pra quem não assina
 * o FixaOS completo) — Etapa 2 do pedido. Mesmo formato/filosofia de config/planos.php:
 * `preco_mensal` em CENTAVOS, preço do ciclo = preco_mensal × meses × (1 − desconto%), via o
 * MESMO helper `plano_preco_ciclo()` já usado pro resto do sistema (não reimplementado aqui).
 *
 * Confirmado com os 6 valores passados pelo usuário: os 3 ciclos antecipados (trimestral/
 * semestral/anual) batem exatos com 5% de desconto sobre o preço mensal — só o percentual é
 * guardado, não cada valor calculado, pra nunca divergir se o preço mensal mudar aqui.
 *
 * `fixa_diretorio` inclui tudo do `fixa_individual` + diretório completo (logo, fotos, vitrine,
 * contagem de visitas, responder avaliações) — a checagem de "incluído" na tela/controller
 * sempre testa por código, não por preço, pra não quebrar se os valores mudarem.
 *
 * Quem já assina um plano pago do FixaOS (ver config/planos.php — 'autonomo'/'oficina'/
 * 'empresa', 'basico' NÃO inclui) ganha o Fixa de graça — ver financeiro_pessoal_liberado() —
 * então nenhuma destas cobranças se aplica a essas empresas; isso é tratado no código, não aqui.
 */
return [
    'ciclos' => [
        'mensal'     => ['nome' => 'Mensal',     'meses' => 1,  'dias' => 30,  'desconto' => 0],
        'trimestral' => ['nome' => 'Trimestral', 'meses' => 3,  'dias' => 90,  'desconto' => 5],
        'semestral'  => ['nome' => 'Semestral',  'meses' => 6,  'dias' => 182, 'desconto' => 5],
        'anual'      => ['nome' => 'Anual',       'meses' => 12, 'dias' => 365, 'desconto' => 5],
    ],

    'planos' => [
        [
            'codigo' => 'fixa_individual', 'nome' => 'Carteira Fixa Individual', 'preco_mensal' => 990,
            'max_usuarios' => 1, 'inclui_diretorio' => false,
            'beneficios' => [
                'Financeiro pessoal completo — Pessoal (CPF) e Empresa/MEI (CNPJ)',
                'Agenda integrada (vencimentos, lembretes)',
                'Scanner de contas por foto (até 100 leituras/mês)',
                '1 usuário',
            ],
        ],
        [
            'codigo' => 'fixa_diretorio', 'nome' => 'Carteira Fixa + Diretório', 'preco_mensal' => 1990,
            'max_usuarios' => 1, 'inclui_diretorio' => true,
            'beneficios' => [
                'Tudo do Carteira Fixa Individual',
                'Diretório completo — logo, fotos, vitrine',
                'Contagem de visitas ao perfil',
                'Responder avaliações de clientes',
                '1 usuário',
            ],
        ],
    ],

    // Teste grátis — nunca mais que isto, em NENHUM fluxo (cupom/indicação/plano antecipado
    // nunca estendem). Ver AssinaturaService::criarTeste().
    'teste_dias' => 7,

    // Leituras do scanner de contas por mês pra quem tem Fixa STANDALONE (quem tem Fixa de
    // graça por um plano pago do FixaOS usa o limite do próprio plano — scan_fixa_conta_mes
    // em config/planos.php — não este). Ver fixa_scanner_verificar().
    'scanner_leituras_mes' => 100,

    // Indicação: R$5 (centavos) de desconto na mensalidade SEGUINTE de quem indicou, por
    // indicado que pagou a 1ª cobrança (nunca na cobrança do próprio indicado).
    'indicacao_desconto_centavos' => 500,
];
