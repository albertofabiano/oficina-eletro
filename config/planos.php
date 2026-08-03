<?php
/*
 * Planos do FixaOS — 3 planos × 3 ciclos de cobrança.
 * ⚠️ VALORES SÃO PROPOSTA — o dono ajusta aqui (arquivo único).
 * `preco_mensal` em CENTAVOS. Preço do ciclo = preco_mensal × meses × (1 - desconto%).
 * Limites: 0 = ILIMITADO.
 * `vagas_promo`: nº de assinantes reais (pagamento confirmado) que ainda pagam `preco_mensal`.
 * Esgotado (assinantes >= vagas_promo): novos assinantes pagam `preco_pos_intro` desde o 1º mês.
 * Sem essa chave = sem cota, preço normal pra sempre (com ou sem intro_meses).
 */
return [
    'ciclos' => [
        'mensal'     => ['nome' => 'Mensal',     'meses' => 1,  'dias' => 30,  'desconto' => 0],
        'trimestral' => ['nome' => 'Trimestral', 'meses' => 3,  'dias' => 90,  'desconto' => 15],
        'anual'      => ['nome' => 'Anual',       'meses' => 12, 'dias' => 365, 'desconto' => 20],
    ],

    'planos' => [
        [
            'codigo' => 'autonomo', 'nome' => 'Autônomo', 'preco_mensal' => 2990,
            'preco_pos_intro' => 5990, 'intro_meses' => 12, 'vagas_promo' => 300,
            'max_usuarios' => 2, 'os_mes' => 60, 'max_produtos' => 20, 'destaque' => false,
            'scan_equip_mes' => 40, 'scan_placa_mes' => 20,
            'beneficios' => ['Sistema completo de OS', '2 usuários', '60 OS por mês', 'Editar página no diretório', '20 produtos no marketplace', 'Crédito para +OS quando precisar'],
        ],
        [
            'codigo' => 'oficina', 'nome' => 'Oficina', 'preco_mensal' => 5990,
            'max_usuarios' => 5, 'os_mes' => 150, 'max_produtos' => 100, 'destaque' => true,
            'scan_equip_mes' => 90, 'scan_placa_mes' => 40,
            'beneficios' => ['Tudo do Autônomo', '5 usuários', '150 OS por mês', 'WhatsApp pelo número próprio', '100 produtos', 'Destaque no diretório'],
        ],
        [
            'codigo' => 'empresa', 'nome' => 'Top Empresa', 'preco_mensal' => 11990,
            'max_usuarios' => 0, 'os_mes' => 500, 'max_produtos' => 0, 'destaque' => false,
            'scan_equip_mes' => 300, 'scan_placa_mes' => 150,
            'beneficios' => ['Tudo do Oficina', 'Usuários ilimitados', '500 OS por mês', 'Produtos ilimitados', 'Destaque premium', 'Suporte prioritário'],
        ],
    ],

    // Pacote de crédito de OS extra (excedente do Autônomo/Oficina). +25 OS por R$24,90.
    'credito_os' => ['qtd' => 25, 'preco' => 2490],

    // Pacotes de crédito de buscas de IA extra (quando estourar o limite mensal do plano).
    'credito_scan_equip' => ['qtd' => 20, 'preco' => 1490], // +20 buscas de equipamento por R$14,90
    'credito_scan_placa' => ['qtd' => 20, 'preco' => 990],  // +20 buscas de placa por R$9,90
];
