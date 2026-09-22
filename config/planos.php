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
 * Os dois são 0 (ilimitado) nos 4 planos hoje — o campo continua existindo, plano a plano,
 * caso o dono decida voltar a diferenciar por quantidade no futuro; não precisa mexer em código
 * pra isso, só nos valores aqui.
 * `scan_equip_habilitado`/`scan_placa_habilitado`/`mentor_habilitado`/`estoque_imagem_habilitado`:
 * eixo DIFERENTE do limite numérico acima — false desliga a função por completo pro plano (não é
 * "sem limite", é "sem acesso"; nem crédito avulso comprado destrava). Ausente = true (feature
 * ligada), então os planos que já existiam antes dessas chaves continuam exatamente como estavam.
 * `estoque_imagem_habilitado=false` (Básico e Autônomo): o cadastro de produto no ESTOQUE não
 * aceita foto (capa/galeria) — a única forma de dar cara ao produto nesses planos é publicando
 * ele no Marketplace ou na vitrine do Diretório, que sempre aceitam foto, em qualquer plano
 * (ver ProdutoController::estoqueImagemHabilitada(), MarketplaceController, DiretorioProdutosController).
 * `fotos_entrada_habilitado=false` (só Básico): bloqueia "Tirar foto do estado do aparelho" no
 * wizard de Nova OS (pareamento por QR, sem IA nenhuma envolvida — é só upload de foto) — mesma
 * trava do Básico pra "Usar o celular para preencher" (`scan_equip_habilitado`), então as duas
 * telas dependem só de o plano ser Básico ou não (ver OrdemServicoController::
 * recursosAutonomoHabilitados()).
 * `whatsapp_proprio_habilitado=false` (só Básico): bloqueia conectar o WhatsApp da própria loja
 * (Evolution API, `EmpresaController::whatsapp()`) — sem isso, Básico continua mandando
 * mensagem só pelo número da plataforma (`WhatsAppService::enviarTextoPlataforma()`), igual
 * antes de existir conexão própria nenhuma.
 * `vagas_promo`: nº de assinantes reais (pagamento confirmado) que ainda pagam `preco_mensal`.
 * Esgotado (assinantes >= vagas_promo): novos assinantes pagam `preco_pos_intro` desde o 1º mês.
 * Sem essa chave = sem cota, preço normal pra sempre (com ou sem intro_meses).
 * Ordem do array = ordem de exibição nas telas de planos; o fallback "sem plano escolhido"
 * usa sempre o código 'autonomo' (app/Helpers/functions.php), não a posição no array — dá
 * pra reordenar aqui à vontade sem quebrar esse fallback.
 */
return [
    'ciclos' => [
        'mensal'     => ['nome' => 'Mensal',     'meses' => 1,  'dias' => 30,  'desconto' => 0],
        'trimestral' => ['nome' => 'Trimestral', 'meses' => 3,  'dias' => 90,  'desconto' => 15],
        'anual'      => ['nome' => 'Anual',       'meses' => 12, 'dias' => 365, 'desconto' => 20],
    ],

    'planos' => [
        [
            'codigo' => 'basico', 'nome' => 'Básico', 'preco_mensal' => 1900,
            'max_usuarios' => 1, 'os_mes' => 30, 'max_produtos' => 0, 'max_produtos_diretorio' => 0,
            'destaque' => false, 'scan_equip_habilitado' => false, 'mentor_habilitado' => false,
            'estoque_imagem_habilitado' => false, 'fotos_entrada_habilitado' => false,
            'whatsapp_proprio_habilitado' => false,
            'scan_placa_mes' => 10,
            'beneficios' => ['PDV / frente de caixa', 'Página pública no Diretório', '1 usuário', '30 OS por mês', 'Estoque de produtos ilimitado (sem foto)', 'Vitrine do Marketplace ilimitada, com foto', 'Fluxo de caixa conectado à Agenda automaticamente', 'Sem cadastro automático por foto (preencha manualmente)', 'Sem Mentor IA', 'Crédito para +OS quando precisar'],
        ],
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
