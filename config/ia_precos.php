<?php
/*
 * Preço por modelo da Anthropic, pra calcular o custo estimado de cada chamada a partir do
 * `usage` REAL da resposta (nunca um valor fixo "por leitura" — a Etapa 4 pediu exatamente
 * isso: "Registrar em cada chamada ... tokens de entrada e saída e custo estimado").
 *
 * Valores em USD por 1 milhão de tokens, confirmados via busca na documentação/imprensa da
 * Anthropic em 08/10/2026 (preço oficial não pôde ser aberto direto da platform.claude.com
 * nesta sessão, mas bateu entre 3+ fontes independentes pra cada modelo):
 *   - Haiku 4.5  (claude-haiku-4-5-20251001): $1,00 / $5,00 (entrada/saída)
 *   - Sonnet 5.5 (claude-sonnet-5-5):         $2,00 / $10,00 (entrada/saída)
 *   - Haiku 5.5  (claude-haiku-5-5):          $0,10 / $0,50 (entrada/saída, até 100k tokens de
 *     prompt — usado pelo Lançamento por voz do Carteira Fixa, ver
 *     FinanceiroPessoalController::vozExtrair())
 *
 * Modelo chamado que não está nesta lista: custo fica 0 (nunca inventa um preço) — a tela do
 * Master mostra os tokens normalmente, só o custo em R$ fica zerado até alguém cadastrar o
 * preço aqui.
 *
 * `dolar_brl`: cotação usada pra converter USD→BRL no cálculo — ajuste manual aqui quando
 * variar muito; não busca cotação em tempo real (overhead desnecessário pra uma estimativa).
 */
return [
    'dolar_brl' => 5.50,

    'modelos' => [
        'claude-haiku-4-5-20251001' => ['input_usd_mtok' => 1.00, 'output_usd_mtok' => 5.00],
        'claude-haiku-4-5'          => ['input_usd_mtok' => 1.00, 'output_usd_mtok' => 5.00],
        'claude-sonnet-5-5'         => ['input_usd_mtok' => 2.00, 'output_usd_mtok' => 10.00],
        'claude-haiku-5-5'          => ['input_usd_mtok' => 0.10, 'output_usd_mtok' => 0.50],
    ],
];
