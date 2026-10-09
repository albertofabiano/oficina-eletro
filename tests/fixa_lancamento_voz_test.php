<?php
/*
 * Lançamento por voz — testa a parte PURA de FinanceiroPessoalController::vozChamarIA(), ou
 * seja, tudo que roda DEPOIS do JSON já ter vindo da IA (mapeamento tipo/valor/categoria/conta,
 * clamp de confiança, override por regra aprendida). Não dá pra testar o método real de ponta a
 * ponta sem rede (chama IAService::perguntar() -> Anthropic de verdade) nem sem instanciar o
 * controller inteiro (construtor pesado: DB, sessão, resolução de empresa/perfil) — então, mesma
 * convenção já usada no resto do projeto ("réplica isolada... testado com PDO fake"), o
 * pós-processamento foi copiado linha a linha do controller (ver vozChamarIA() em
 * app/Controllers/FinanceiroPessoalController.php, a partir de "$categoriaIA = ...") pra uma
 * função local, substituindo só a consulta ao banco (financeiro_pessoal_categoria_aprendida())
 * por um closure injetado — o resto é código real, não reimplementado.
 *
 * As funções que ESTA réplica chama de verdade (não reimplementadas): VisionService::
 * normalizarData() (resolução de data) e financeiro_pessoal_categoria_aprendida_em_texto()
 * (pré-checagem antes da IA, já teste em tests/fixa_aprendizado_categoria_test.php).
 *
 * Rodar com: php tests/fixa_lancamento_voz_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Helpers/functions.php';
require BASE_PATH . '/app/Services/VisionService.php';

$falhas = 0; $total = 0;
function assert_igual($esperado, $obtido, string $descricao): void
{
    global $falhas, $total;
    $total++;
    if ($esperado === $obtido) { echo "  OK  $descricao\n"; return; }
    $falhas++;
    echo "FALHA $descricao\n      esperado: " . var_export($esperado, true) . "\n      obtido:   " . var_export($obtido, true) . "\n";
}
function assert_nulo($obtido, string $descricao): void
{
    global $falhas, $total;
    $total++;
    if ($obtido === null) { echo "  OK  $descricao\n"; return; }
    $falhas++;
    echo "FALHA $descricao\n      esperado: null\n      obtido:   " . var_export($obtido, true) . "\n";
}

/**
 * Réplica EXATA do pós-processamento de vozChamarIA() (a partir do $d já parseado da resposta
 * da IA) — mesmas linhas, só troca o SELECT direto no banco por um closure.
 */
function replicaPosProcessamentoVoz(array $d, string $texto, array $categorias, array $contas, ?array $regraPrevia, callable $buscarRegraPorBeneficiario): array
{
    $padrao = [
        'tipo' => 'despesa', 'descricao' => $texto, 'valor' => 0.0,
        'categoria' => array_key_first($categorias) ?? 'outros', 'conta_id' => $contas[0]['id'] ?? null,
        'data' => date('Y-m-d'), 'beneficiario' => '', 'confianca' => 0.0, 'aprendido' => false,
    ];

    $categoriaIA = (string) ($d['categoria_sugerida'] ?? '');
    if (!array_key_exists($categoriaIA, $categorias)) $categoriaIA = $padrao['categoria'];

    $idsContasValidas = array_map('intval', array_column($contas, 'id'));
    $contaIA = isset($d['conta_sugerida']) ? (int) $d['conta_sugerida'] : null;
    $contaValida = $contaIA && in_array($contaIA, $idsContasValidas, true) ? $contaIA : null;

    $tipo = ((string) ($d['tipo'] ?? '')) === 'entrada' ? 'receita' : 'despesa';
    $descricao = trim((string) ($d['descricao'] ?? '')) ?: $texto;
    $beneficiario = trim((string) ($d['beneficiario'] ?? ''));
    $data = \App\Services\VisionService::normalizarData((string) ($d['data'] ?? '')) ?: date('Y-m-d');
    $valor = max(0, (int) ($d['valor_centavos'] ?? 0)) / 100;
    $confianca = min(1, max(0, (float) ($d['confianca'] ?? 0)));

    $aprendido = false;
    $regra = $regraPrevia;
    if ($regra === null && $beneficiario !== '') {
        $regra = $buscarRegraPorBeneficiario($beneficiario);
    }
    if ($regra !== null && array_key_exists($regra['categoria'], $categorias)) {
        $categoriaIA = $regra['categoria'];
        if ($regra['conta_id'] !== null && in_array($regra['conta_id'], $idsContasValidas, true)) {
            $contaValida = $regra['conta_id'];
        }
        $aprendido = true;
    }

    return [
        'tipo' => $tipo, 'descricao' => mb_substr($descricao, 0, 150), 'valor' => $valor,
        'categoria' => $categoriaIA, 'conta_id' => $contaValida, 'data' => $data,
        'beneficiario' => $beneficiario, 'confianca' => $confianca, 'aprendido' => $aprendido,
    ];
}

$categorias = [
    'alimentacao'         => ['nome' => 'Alimentação', 'tipo' => 'despesa'],
    'lazer'               => ['nome' => 'Lazer', 'tipo' => 'despesa'],
    'outras'               => ['nome' => 'Outras', 'tipo' => 'despesa'],
    'fornecedores_pecas'  => ['nome' => 'Fornecedores e peças', 'tipo' => 'despesa'],
    'salario'             => ['nome' => 'Salário', 'tipo' => 'receita'],
];
$contas = [
    ['id' => 1, 'nome' => 'Conta Pessoal'],
    ['id' => 2, 'nome' => 'Conta PJ'],
];
$nuncaAchaRegra = function (string $b): ?array { return null; };

echo "== tipo: gasto/entrada -> despesa/receita ==\n";
{
    $r1 = replicaPosProcessamentoVoz(['tipo' => 'entrada'], 'x', $categorias, $contas, null, $nuncaAchaRegra);
    assert_igual('receita', $r1['tipo'], '"entrada" mapeia pra receita');

    $r2 = replicaPosProcessamentoVoz(['tipo' => 'gasto'], 'x', $categorias, $contas, null, $nuncaAchaRegra);
    assert_igual('despesa', $r2['tipo'], '"gasto" mapeia pra despesa');

    $r3 = replicaPosProcessamentoVoz(['tipo' => 'lixo'], 'x', $categorias, $contas, null, $nuncaAchaRegra);
    assert_igual('despesa', $r3['tipo'], 'valor desconhecido/ausente cai em despesa (nunca quebra)');
}

echo "\n== valor_centavos -> reais ==\n";
{
    $r1 = replicaPosProcessamentoVoz(['valor_centavos' => 3500], 'x', $categorias, $contas, null, $nuncaAchaRegra);
    assert_igual(35.0, (float) $r1['valor'], '3500 centavos = R$35,00');

    $r2 = replicaPosProcessamentoVoz(['valor_centavos' => 6890], 'x', $categorias, $contas, null, $nuncaAchaRegra);
    assert_igual(68.9, (float) $r2['valor'], '6890 centavos = R$68,90');

    $r3 = replicaPosProcessamentoVoz(['valor_centavos' => -500], 'x', $categorias, $contas, null, $nuncaAchaRegra);
    assert_igual(0.0, (float) $r3['valor'], 'valor negativo nunca passa (clamp em 0)');

    $r4 = replicaPosProcessamentoVoz([], 'x', $categorias, $contas, null, $nuncaAchaRegra);
    assert_igual(0.0, (float) $r4['valor'], 'valor_centavos ausente = 0');
}

echo "\n== categoria: valida contra a lista do perfil, senão cai no fallback ==\n";
{
    $r1 = replicaPosProcessamentoVoz(['categoria_sugerida' => 'lazer'], 'x', $categorias, $contas, null, $nuncaAchaRegra);
    assert_igual('lazer', $r1['categoria'], 'categoria válida é mantida');

    $r2 = replicaPosProcessamentoVoz(['categoria_sugerida' => 'categoria-que-nao-existe'], 'x', $categorias, $contas, null, $nuncaAchaRegra);
    assert_igual(array_key_first($categorias), $r2['categoria'], 'categoria inventada pela IA cai no fallback (primeira do perfil)');

    $r3 = replicaPosProcessamentoVoz([], 'x', $categorias, $contas, null, $nuncaAchaRegra);
    assert_igual(array_key_first($categorias), $r3['categoria'], 'sem categoria_sugerida nenhuma: fallback');
}

echo "\n== conta: só aceita id que de fato existe nas contas do perfil ==\n";
{
    $r1 = replicaPosProcessamentoVoz(['conta_sugerida' => 2], 'x', $categorias, $contas, null, $nuncaAchaRegra);
    assert_igual(2, $r1['conta_id'], 'conta 2 (PJ) existe: aceita');

    $r2 = replicaPosProcessamentoVoz(['conta_sugerida' => '2'], 'x', $categorias, $contas, null, $nuncaAchaRegra);
    assert_igual(2, $r2['conta_id'], 'conta vindo como STRING da IA também bate (array_map intval nos ids da base)');

    $r3 = replicaPosProcessamentoVoz(['conta_sugerida' => 999], 'x', $categorias, $contas, null, $nuncaAchaRegra);
    assert_nulo($r3['conta_id'], 'conta inexistente: null (nunca grava um id forjado)');

    $r4 = replicaPosProcessamentoVoz(['conta_sugerida' => null], 'x', $categorias, $contas, null, $nuncaAchaRegra);
    assert_nulo($r4['conta_id'], 'sem menção de conta na frase (null): continua null');

    $r5 = replicaPosProcessamentoVoz(['conta_sugerida' => 0], 'x', $categorias, $contas, null, $nuncaAchaRegra);
    assert_nulo($r5['conta_id'], 'conta 0 (falsy) nunca é tratada como id válido');
}

echo "\n== confiança: sempre clampada em [0,1] ==\n";
{
    $r1 = replicaPosProcessamentoVoz(['confianca' => 1.4], 'x', $categorias, $contas, null, $nuncaAchaRegra);
    assert_igual(1.0, (float) $r1['confianca'], 'acima de 1 vira 1');

    $r2 = replicaPosProcessamentoVoz(['confianca' => -0.3], 'x', $categorias, $contas, null, $nuncaAchaRegra);
    assert_igual(0.0, (float) $r2['confianca'], 'negativa vira 0');

    $r3 = replicaPosProcessamentoVoz(['confianca' => 0.42], 'x', $categorias, $contas, null, $nuncaAchaRegra);
    assert_igual(0.42, (float) $r3['confianca'], 'valor normal passa direto');
}

echo "\n== descrição: trunca em 150 chars, cai pra transcrição se vier vazia ==\n";
{
    $longa = str_repeat('a', 200);
    $r1 = replicaPosProcessamentoVoz(['descricao' => $longa], 'x', $categorias, $contas, null, $nuncaAchaRegra);
    assert_igual(150, mb_strlen($r1['descricao']), 'descrição é truncada em 150 caracteres');

    $r2 = replicaPosProcessamentoVoz(['descricao' => ''], 'texto ouvido original', $categorias, $contas, null, $nuncaAchaRegra);
    assert_igual('texto ouvido original', $r2['descricao'], 'descrição vazia da IA cai pro texto transcrito');
}

echo "\n== regra aprendida vence a categoria/conta sugerida pela IA (pré-checagem ANTES da IA) ==\n";
{
    // "pix pra Miguel 150" — primeira vez, sem regra: a IA sugere o que achar (ex. 'outras').
    $r1 = replicaPosProcessamentoVoz(
        ['tipo' => 'gasto', 'valor_centavos' => 15000, 'categoria_sugerida' => 'outras', 'beneficiario' => 'Miguel'],
        'pix pra Miguel 150', $categorias, $contas, null, $nuncaAchaRegra
    );
    assert_igual('outras', $r1['categoria'], 'sem regra: categoria vem da IA (ex. "Outras")');
    assert_igual(false, $r1['aprendido'], 'sem regra: não marca como aprendido');
    assert_igual(150.0, (float) $r1['valor'], 'valor extraído corretamente (15000 centavos = R$150)');

    // Segunda vez: já existe regra "miguel" -> "fornecedores_pecas" (usuário corrigiu da 1ª vez).
    // Mesma frase, regraPrevia já vem preenchida (pré-checagem ANTES da chamada de IA) — a IA
    // ainda roda (extrai valor/data/tipo), mas a categoria final não é mais a sugerida por ela.
    $regraPreviaAchada = ['categoria' => 'fornecedores_pecas', 'conta_id' => null, 'usos' => 1, 'confirmada' => false, 'termo' => 'miguel'];
    $r2 = replicaPosProcessamentoVoz(
        ['tipo' => 'gasto', 'valor_centavos' => 15000, 'categoria_sugerida' => 'outras', 'beneficiario' => 'Miguel'],
        'pix pra Miguel 150', $categorias, $contas, $regraPreviaAchada, $nuncaAchaRegra
    );
    assert_igual('fornecedores_pecas', $r2['categoria'], 'com regra: categoria aprendida vence a sugestão da IA');
    assert_igual(true, $r2['aprendido'], 'com regra: marca como aprendido (selo na tela)');
}

echo "\n== regra achada só DEPOIS da IA, pelo beneficiário que ela extraiu (pré-check não bateu) ==\n";
{
    $buscaPorBeneficiario = function (string $b) {
        return $b === 'Enel Distribuição SP' ? ['categoria' => 'moradia', 'conta_id' => 2, 'usos' => 2, 'confirmada' => true] : null;
    };
    $catsComMoradia = $categorias + ['moradia' => ['nome' => 'Moradia', 'tipo' => 'despesa']];
    $r = replicaPosProcessamentoVoz(
        ['tipo' => 'gasto', 'valor_centavos' => 20000, 'categoria_sugerida' => 'outras', 'beneficiario' => 'Enel Distribuição SP'],
        'pagar a conta de luz', $catsComMoradia, $contas, null, $buscaPorBeneficiario
    );
    assert_igual('moradia', $r['categoria'], 'achou a regra pelo beneficiário extraído pela IA (pré-check por substring não bateu antes)');
    assert_igual(2, $r['conta_id'], 'conta da regra também é aplicada, já que é uma conta válida do perfil');
    assert_igual(true, $r['aprendido'], 'marca como aprendido mesmo achando só depois da IA');
}

echo "\n== regra com conta_id que NÃO é mais válida no perfil: categoria aplica, conta não ==\n";
{
    $regraContaInvalida = ['categoria' => 'lazer', 'conta_id' => 999, 'usos' => 2, 'confirmada' => true];
    $r = replicaPosProcessamentoVoz(
        ['tipo' => 'gasto', 'valor_centavos' => 1000, 'categoria_sugerida' => 'outras', 'conta_sugerida' => 1, 'beneficiario' => 'x'],
        'x', $categorias, $contas, $regraContaInvalida, $nuncaAchaRegra
    );
    assert_igual('lazer', $r['categoria'], 'categoria da regra aplica normalmente');
    assert_igual(1, $r['conta_id'], 'conta da regra (999, inexistente) é ignorada — mantém a conta válida que a IA já tinha sugerido');
}

echo "\n== exemplo do pedido: 'ingresso no cinema e pipoca 68,90' -> um lançamento só, Lazer ==\n";
{
    $r = replicaPosProcessamentoVoz(
        ['tipo' => 'gasto', 'valor_centavos' => 6890, 'categoria_sugerida' => 'lazer', 'descricao' => 'Cinema e pipoca'],
        'ingresso no cinema e pipoca 68,90', $categorias, $contas, null, $nuncaAchaRegra
    );
    assert_igual('despesa', $r['tipo'], 'um único lançamento (não dois)');
    assert_igual(68.9, (float) $r['valor'], 'valor combinado dos dois itens falados numa frase só');
    assert_igual('lazer', $r['categoria'], 'categoria Lazer');
    assert_igual('Cinema e pipoca', $r['descricao'], 'descrição resume os dois itens');
}

echo "\n------------------------------------------------------------\n";
echo "$total verificações, $falhas falha(s).\n";
exit($falhas > 0 ? 1 : 0);
