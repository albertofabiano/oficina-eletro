<?php
/*
 * Planos do FixaOS — 3 planos × 3 ciclos de cobrança.
 * ⚠️ VALORES SÃO PROPOSTA — o dono ajusta aqui (arquivo único).
 * `preco_mensal` em CENTAVOS. Preço do ciclo = preco_mensal × meses × (1 - desconto%).
 * Limites (max_usuarios, os_mes, max_produtos, max_produtos_diretorio, scan_equip_mes,
 * scan_placa_mes): 0 = ILIMITADO. O Top Empresa é o único plano com TODOS esses campos
 * zerados — literalmente sem teto em nada (usuário, OS/mês, estoque, vitrine, buscas de IA).
 * `max_produtos` é o total de produtos no ESTOQUE (ProdutoController, tabela `produtos`) --
 * apesar do nome parecido, é DIFERENTE de `max_produtos_diretorio`, o teto de itens na vitrine
 * pública do Diretório/Marketplace (DiretorioProdutosController, tabela `diretorio_produtos`).
 * Os dois são 0 (ilimitado) nos 3 planos hoje — o campo continua existindo, plano a plano,
 * caso o dono decida voltar a diferenciar por quantidade no futuro; não precisa mexer em código
 * pra isso, só nos valores aqui.
 * `estoque_imagem_habilitado`/`mentor_habilitado`: eixo DIFERENTE do limite numérico acima —
 * false desliga a função por completo pro plano (não é "sem limite", é "sem acesso"; nem
 * crédito avulso comprado destrava). Ausente = true (feature ligada). Hoje só
 * `estoque_imagem_habilitado=false` no Autônomo está em uso (o cadastro de produto no ESTOQUE
 * não aceita foto ali — a única forma de dar cara ao produto nesse plano é publicando ele no
 * Marketplace ou na vitrine do Diretório, que sempre aceitam foto, em qualquer plano; ver
 * ProdutoController::estoqueImagemHabilitada(), MarketplaceController, DiretorioProdutosController).
 * `vagas_promo`: nº de assinantes reais (pagamento confirmado) que ainda pagam `preco_mensal`.
 * Esgotado (assinantes >= vagas_promo): novos assinantes pagam `preco_pos_intro` desde o 1º mês.
 * Sem essa chave = sem cota, preço normal pra sempre (com ou sem intro_meses).
 * Ordem do array = ordem de exibição nas telas de planos; o fallback "sem plano escolhido"
 * usa sempre o código 'autonomo' (app/Helpers/functions.php), não a posição no array — dá
 * pra reordenar aqui à vontade sem quebrar esse fallback.
 *
 * Plano "Básico" (R$19,90, 1 usuário) removido a pedido do dono -- não fazia sentido como
 * plano de verdade (perto demais do Autônomo em preço, mas sem WhatsApp próprio nem cadastro
 * por foto, os dois recursos mais anunciados do sistema). Toda a gate exclusiva dele (scan por
 * foto/etiqueta, foto do estado de entrada, conexão de WhatsApp própria via API) foi removida
 * junto -- nenhum plano restante bloqueia mais esses recursos. `plano_da_empresa()` já cai
 * sozinho no Autônomo pra qualquer empresa antiga que ainda tenha `plano_atual='basico'`
 * salvo (fallback por código, não por posição no array) -- sem precisar de migração de dado.
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
            'max_usuarios' => 2, 'os_mes' => 60, 'max_produtos' => 0, 'max_produtos_diretorio' => 0, 'destaque' => false,
            'estoque_imagem_habilitado' => false,
            'scan_equip_mes' => 40, 'scan_placa_mes' => 20,
            'beneficios' => ['Sistema completo de OS', '2 usuários', '60 OS por mês', 'Editar página no diretório', 'WhatsApp pelo número próprio', 'Estoque de produtos ilimitado (sem foto)', 'Vitrine do Marketplace ilimitada, com foto', 'Fluxo de caixa conectado à Agenda automaticamente', 'Crédito para +OS quando precisar'],
        ],
        [
            'codigo' => 'oficina', 'nome' => 'Oficina', 'preco_mensal' => 5990,
            'max_usuarios' => 5, 'os_mes' => 150, 'max_produtos' => 0, 'max_produtos_diretorio' => 0, 'destaque' => true,
            'scan_equip_mes' => 90, 'scan_placa_mes' => 40,
            'beneficios' => ['Tudo do Autônomo, mais:', '5 usuários', '150 OS por mês', 'Estoque de produtos ilimitado, com foto também no estoque', 'Destaque no diretório', 'Fluxo de caixa conectado à Agenda automaticamente'],
        ],
        [
            'codigo' => 'empresa', 'nome' => 'Top Empresa', 'preco_mensal' => 11990,
            'max_usuarios' => 0, 'os_mes' => 0, 'max_produtos' => 0, 'max_produtos_diretorio' => 0, 'destaque' => false,
            'scan_equip_mes' => 0, 'scan_placa_mes' => 0,
            'beneficios' => ['Tudo do Oficina, mais:', 'Usuários ilimitados', 'OS ilimitadas por mês', 'Estoque de produtos ilimitado, com foto também no estoque', 'Cadastro por foto e leitura de placa ilimitados', 'Destaque premium', 'Suporte prioritário', 'Fluxo de caixa conectado à Agenda automaticamente'],
        ],
    ],

    // Pacote de crédito de OS extra (excedente do Autônomo/Oficina). +25 OS por R$24,90.
    'credito_os' => ['qtd' => 25, 'preco' => 2490],

    // Pacotes de crédito de buscas de IA extra (quando estourar o limite mensal do plano).
    'credito_scan_equip' => ['qtd' => 20, 'preco' => 1490], // +20 buscas de equipamento por R$14,90
    'credito_scan_placa' => ['qtd' => 20, 'preco' => 990],  // +20 buscas de placa por R$9,90
];
