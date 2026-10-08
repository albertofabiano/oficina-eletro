<?php
/*
 * Planos do FixaOS — 4 planos × 3 ciclos de cobrança.
 * ⚠️ VALORES SÃO PROPOSTA — o dono ajusta aqui (arquivo único).
 * `preco_mensal` em CENTAVOS. Preço do ciclo = preco_mensal × meses × (1 - desconto%).
 * Limites (max_usuarios, os_mes, max_produtos, max_produtos_diretorio, scan_equip_mes,
 * scan_placa_mes): 0 = ILIMITADO. O Top Empresa é o único plano com TODOS esses campos
 * zerados — literalmente sem teto em nada (usuário, OS/mês, estoque, vitrine, buscas de IA).
 * `max_produtos` é o total de produtos no ESTOQUE (ProdutoController, tabela `produtos`) --
 * apesar do nome parecido, é DIFERENTE de `max_produtos_diretorio`, o teto de itens na vitrine
 * pública do Diretório/Marketplace (DiretorioProdutosController, tabela `diretorio_produtos`).
 * `estoque_imagem_habilitado`/`mentor_habilitado`/`scan_equip_habilitado`/
 * `whatsapp_proprio_habilitado`: eixo DIFERENTE do limite numérico acima — false desliga a
 * função por completo pro plano (não é "sem limite", é "sem acesso"; nem crédito avulso
 * comprado destrava). Ausente = true (feature ligada).
 * `modulos_bloqueados`: lista de módulos (mesmo nome usado por Auth::moduloDoUri()) que o plano
 * não libera NEM PRA ADMIN — checado em AuthMiddleware, eixo diferente da permissão por papel
 * (Auth::can()), que continua valendo por cima disso. Ausente/vazio = nenhum módulo extra
 * bloqueado (além do que o papel do usuário já filtra). `divulgacao_habilitado=false`: bloqueia
 * as 4 telas de Divulgação que dependem de plano pago (Editar Diretório, Produtos no Diretório,
 * Anúncios, Vagas de Emprego — rotas sob `/empresa/*`, que o moduloDoUri() já lumping em
 * 'config' junto com configurações essenciais da conta, por isso não dá pra usar
 * `modulos_bloqueados` pra isso sem também bloquear `/empresa` inteiro).
 * `vagas_promo`: nº de assinantes reais (pagamento confirmado) que ainda pagam `preco_mensal`.
 * Esgotado (assinantes >= vagas_promo): novos assinantes pagam `preco_pos_intro` desde o 1º mês.
 * Sem essa chave = sem cota, preço normal pra sempre (com ou sem intro_meses).
 * Ordem do array = ordem de exibição nas telas de planos; o fallback "sem plano escolhido"
 * usa sempre o código 'autonomo' (app/Helpers/functions.php), não a posição no array — dá
 * pra reordenar aqui à vontade sem quebrar esse fallback.
 *
 * Plano "Básico" (R$19,90) recriado a pedido do usuário (2026), depois de uma primeira versão
 * ter sido removida por "não fazer sentido perto do Autônomo" — desta vez bem mais enxuto de
 * propósito, só OS + Financeiro + Cadastro de produto (Estoque) + Clientes/Relatórios (que são
 * pré-requisito/consequência direta dos três), sem Agenda/CRM/Marketplace/PDV/Marketing/
 * Divulgação (`modulos_bloqueados` + `divulgacao_habilitado=false`) e sem os dois recursos mais
 * caros de operar (leitura de etiqueta por IA e WhatsApp próprio via Evolution API) —
 * `scan_equip_habilitado=false`/`whatsapp_proprio_habilitado=false`. 1 usuário, sem limite de
 * OS/mês (pedido explícito, pra não competir com o Autônomo em "quantas OS dá pra fazer", só em
 * quais MÓDULOS tem acesso).
 */
return [
    'ciclos' => [
        'mensal'     => ['nome' => 'Mensal',     'meses' => 1,  'dias' => 30,  'desconto' => 0],
        'trimestral' => ['nome' => 'Trimestral', 'meses' => 3,  'dias' => 90,  'desconto' => 15],
        'anual'      => ['nome' => 'Anual',       'meses' => 12, 'dias' => 365, 'desconto' => 20],
    ],

    'planos' => [
        [
            'codigo' => 'basico', 'nome' => 'Básico', 'preco_mensal' => 1990,
            'max_usuarios' => 1, 'os_mes' => 0, 'max_produtos' => 0, 'max_produtos_diretorio' => 0,
            'destaque' => false,
            'scan_equip_habilitado' => false, 'whatsapp_proprio_habilitado' => false,
            'divulgacao_habilitado' => false,
            'modulos_bloqueados' => ['agenda', 'crm', 'marketplace', 'pdv', 'marketing'],
            'beneficios' => [
                'Ordens de Serviço completas — orçamento, laudo técnico e garantia automática',
                'Cadastro de equipamento manual (sem leitura de etiqueta por foto)',
                'Fluxo de Caixa — Financeiro completo',
                'Cadastro de produtos no estoque',
                'Clientes e Relatórios',
                'Link de acompanhamento da OS pro cliente',
                '1 usuário',
                'OS por mês ilimitadas',
                'WhatsApp pelo número da plataforma (sem conexão própria)',
            ],
        ],
        [
            'codigo' => 'autonomo', 'nome' => 'Autônomo', 'preco_mensal' => 2990,
            'max_usuarios' => 2, 'os_mes' => 60, 'max_produtos' => 0, 'max_produtos_diretorio' => 0, 'destaque' => false,
            'estoque_imagem_habilitado' => false,
            'scan_equip_mes' => 40, 'scan_placa_mes' => 20, 'scan_fixa_conta_mes' => 40,
            'beneficios' => [
                'Ordens de Serviço completas — orçamento, laudo técnico rico e garantia automática',
                'Cadastro de equipamento por foto — a câmera lê a etiqueta sozinha',
                'Fotos do estado de entrada do aparelho',
                'Todas as formas de pagamento, com taxa de cartão calculada automaticamente',
                'Link de acompanhamento da OS pro cliente',
                'Agenda com recorrência, lembretes automáticos e rota "Como chegar"',
                'Financeiro conectado — fechamento de OS/PDV lança sozinho, sem duplicar',
                'PDV / frente de caixa',
                '2 usuários',
                '60 OS por mês',
                'Estoque de produtos ilimitado (sem foto)',
                'WhatsApp automático pelo número da própria loja',
                'Editar sua página pública no Diretório (aparece no Google)',
                'Vitrine do Marketplace ilimitada, com foto',
                'Fórum técnico e mural de vagas de emprego',
                'Relatórios e Dashboard do negócio',
                'Crédito para +OS quando precisar',
            ],
        ],
        [
            'codigo' => 'oficina', 'nome' => 'Oficina', 'preco_mensal' => 5990,
            'max_usuarios' => 10, 'os_mes' => 200, 'max_produtos' => 0, 'max_produtos_diretorio' => 0, 'destaque' => true,
            'scan_equip_mes' => 90, 'scan_placa_mes' => 40, 'scan_fixa_conta_mes' => 90,
            'beneficios' => [
                'Ordens de Serviço completas — orçamento, laudo técnico rico e garantia automática',
                'Cadastro de equipamento por foto — a câmera lê a etiqueta sozinha',
                'Fotos do estado de entrada do aparelho',
                'Todas as formas de pagamento, com taxa de cartão calculada automaticamente',
                'Link de acompanhamento da OS pro cliente',
                'Agenda com recorrência, lembretes automáticos e rota "Como chegar"',
                'Financeiro conectado — fechamento de OS/PDV lança sozinho, sem duplicar',
                'PDV / frente de caixa',
                '10 usuários',
                '200 OS por mês',
                'Estoque de produtos ilimitado, com foto também no cadastro geral',
                'WhatsApp automático pelo número da própria loja',
                'Editar sua página pública no Diretório, com destaque (aparece no Google)',
                'Vitrine do Marketplace ilimitada, com foto',
                'Fórum técnico e mural de vagas de emprego',
                'Relatórios e Dashboard do negócio',
                'Crédito para +OS quando precisar',
            ],
        ],
        [
            'codigo' => 'empresa', 'nome' => 'Top Empresa', 'preco_mensal' => 11990,
            'max_usuarios' => 0, 'os_mes' => 0, 'max_produtos' => 0, 'max_produtos_diretorio' => 0, 'destaque' => false,
            'scan_equip_mes' => 0, 'scan_placa_mes' => 0, 'scan_fixa_conta_mes' => 0,
            'beneficios' => [
                'Ordens de Serviço completas — orçamento, laudo técnico rico e garantia automática',
                'Cadastro de equipamento por foto e leitura de placa, ilimitados',
                'Fotos do estado de entrada do aparelho',
                'Todas as formas de pagamento, com taxa de cartão calculada automaticamente',
                'Link de acompanhamento da OS pro cliente',
                'Agenda com recorrência, lembretes automáticos e rota "Como chegar"',
                'Financeiro conectado — fechamento de OS/PDV lança sozinho, sem duplicar',
                'PDV / frente de caixa',
                'Usuários ilimitados',
                'OS ilimitadas por mês',
                'Estoque de produtos ilimitado, com foto também no cadastro geral',
                'WhatsApp automático pelo número da própria loja',
                'Editar sua página pública no Diretório, com destaque premium (aparece no Google)',
                'Vitrine do Marketplace ilimitada, com foto',
                'Fórum técnico e mural de vagas de emprego',
                'Relatórios e Dashboard do negócio',
                'Suporte prioritário',
            ],
        ],
    ],

    // Pacote de crédito de OS extra (excedente do Autônomo/Oficina). +25 OS por R$24,90.
    'credito_os' => ['qtd' => 25, 'preco' => 2490],

    // Pacotes de crédito de buscas de IA extra (quando estourar o limite mensal do plano).
    'credito_scan_equip' => ['qtd' => 20, 'preco' => 1490], // +20 buscas de equipamento por R$14,90
    'credito_scan_placa' => ['qtd' => 20, 'preco' => 990],  // +20 buscas de placa por R$9,90
];
