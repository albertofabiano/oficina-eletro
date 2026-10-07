<?php
/**
 * Popula financeiro_pessoal_lancamentos com 12 meses de dados fictícios pra um usuário de
 * teste — salário médio de R$10.000 e despesas somando entre R$6.000 e R$8.000 por mês (pedido
 * do usuário), suficiente pra dar vida ao Dashboard/gráficos do Financeiro Pessoal (ver
 * migration 075) antes de ter uso real o bastante pra validar visualmente.
 *
 * Por padrão roda em modo SIMULAÇÃO (não grava nada, só mostra o que faria). Pra gravar:
 *   php scripts/seed_financeiro_pessoal_demo.php --aplicar
 *
 * Opções:
 *   --usuario=ID    força o id do usuário (ignora a resolução por nome/empresa abaixo)
 *   --empresa=ID    empresa onde buscar o usuário (padrão: busca por nome_fantasia LIKE
 *                   '%tvservice%' / '%tv service%' — a empresa piloto, TV Service)
 *   --nome=TEXTO    nome do usuário dentro dessa empresa (padrão: 'sergio')
 *   --meses=N       quantos meses gerar, contando do atual pra trás (padrão: 12)
 *   --salario=VALOR salário médio mensal (padrão: 10000)
 *   --forcar        segue mesmo se o usuário já tiver lançamento na janela gerada (por padrão
 *                   o script recusa, pra não duplicar um ano inteiro de dado sem querer)
 *
 * Cada mês sorteia um total de despesa entre R$6.000 e R$8.000 (ajustado por --gasto-min/
 * --gasto-max se precisar de outra faixa) e distribui entre as 6 categorias padrão por peso
 * (moradia/alimentação pesam mais, lazer/saúde menos), quebrando cada fatia em 1-3 lançamentos
 * com descrição realista — a soma de cada mês bate exatamente com o valor sorteado (ajuste de
 * centavos no último item, pra não deixar sobra de arredondamento). O salário varia ±5% em
 * torno da média pedida, não fica cravado no mesmo valor todo mês.
 *
 * Pra apagar depois (ajuste {IDS} pelos ids impressos no resumo final):
 *   DELETE FROM financeiro_pessoal_lancamentos WHERE id IN ({IDS});
 */

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

$aplicar = in_array('--aplicar', $argv, true);
$forcar  = in_array('--forcar', $argv, true);

$argOpt = function (string $nome, $default) use ($argv) {
    foreach ($argv as $a) {
        if (str_starts_with($a, "--{$nome}=")) return substr($a, strlen($nome) + 3);
    }
    return $default;
};
$usuarioArg = $argOpt('usuario', null);
$empresaArg = $argOpt('empresa', null);
$nomeArg    = $argOpt('nome', 'sergio');
$meses      = max(1, (int) $argOpt('meses', 12));
$salarioMedio = (float) $argOpt('salario', 10000);
$gastoMin   = (float) $argOpt('gasto-min', 6000);
$gastoMax   = (float) $argOpt('gasto-max', 8000);

$db = App\Core\DB::pdo();

echo ($aplicar ? "MODO APLICAR — vai gravar de verdade no banco.\n" : "MODO SIMULAÇÃO — nada será gravado (rode com --aplicar pra gravar de verdade).\n");
echo str_repeat('-', 78) . "\n";

// ---------------------------------------------------------------------------------------
// Resolve o usuário
// ---------------------------------------------------------------------------------------

if ($usuarioArg !== null) {
    $stmt = $db->prepare("SELECT u.id, u.nome, u.empresa_id, e.nome_fantasia FROM usuarios u JOIN empresas e ON e.id = u.empresa_id WHERE u.id = ?");
    $stmt->execute([(int) $usuarioArg]);
    $usuario = $stmt->fetch();
} else {
    if ($empresaArg !== null) {
        $stmt = $db->prepare("SELECT id, nome_fantasia FROM empresas WHERE id = ?");
        $stmt->execute([(int) $empresaArg]);
    } else {
        $stmt = $db->query("SELECT id, nome_fantasia FROM empresas WHERE nome_fantasia LIKE '%tvservice%' OR nome_fantasia LIKE '%tv service%' LIMIT 1");
    }
    $empresa = $stmt->fetch();
    if (!$empresa) {
        fwrite(STDERR, "Empresa não encontrada. Use --empresa=ID ou --usuario=ID direto.\n");
        exit(1);
    }
    $stmt = $db->prepare("SELECT id, nome, empresa_id FROM usuarios WHERE empresa_id = ? AND nome LIKE ? AND ativo = 1 LIMIT 1");
    $stmt->execute([$empresa['id'], '%' . $nomeArg . '%']);
    $usuario = $stmt->fetch();
    if ($usuario) $usuario['nome_fantasia'] = $empresa['nome_fantasia'];
}

if (!$usuario) {
    fwrite(STDERR, "Usuário não encontrado. Use --usuario=ID, ou --empresa=ID --nome=TEXTO.\n");
    exit(1);
}

echo "Usuário: {$usuario['nome']} (id {$usuario['id']}) — empresa: {$usuario['nome_fantasia']} (id {$usuario['empresa_id']})\n";
echo str_repeat('-', 78) . "\n";

// ---------------------------------------------------------------------------------------
// Janela: $meses meses completos, do mais antigo pro atual (inclusive, mês corrente também
// gerado por inteiro — isto é um dataset de demonstração, não um recorte "até hoje").
// ---------------------------------------------------------------------------------------

$inicioJanela = (new DateTime('first day of this month'))->modify('-' . ($meses - 1) . ' months')->setTime(0, 0);
$fimJanelaLabel = (new DateTime('last day of this month'));

if (!$forcar) {
    $chk = $db->prepare("SELECT COUNT(*) FROM financeiro_pessoal_lancamentos WHERE usuario_id = ? AND data_hora >= ?");
    $chk->execute([$usuario['id'], $inicioJanela->format('Y-m-d 00:00:00')]);
    $existentes = (int) $chk->fetchColumn();
    if ($existentes > 0) {
        fwrite(STDERR, "Usuário já tem {$existentes} lançamento(s) na janela de {$meses} meses ({$inicioJanela->format('d/m/Y')} até {$fimJanelaLabel->format('d/m/Y')}).\n");
        fwrite(STDERR, "Rode de novo com --forcar se quiser gerar mesmo assim (vai somar, não substitui).\n");
        exit(1);
    }
}

// ---------------------------------------------------------------------------------------
// Pools de despesa por categoria — peso decide a fatia do total mensal, não o valor de cada
// lançamento em si (isso é sorteado pra fechar a fatia exata, ver gerarDespesasDoMes()).
// ---------------------------------------------------------------------------------------

$pools = [
    'moradia' => ['peso' => 32, 'desc' => [
        'Aluguel', 'Condomínio', 'Conta de luz', 'Conta de água', 'Internet', 'Gás', 'Material de limpeza',
    ]],
    'alimentacao' => ['peso' => 26, 'desc' => [
        'Supermercado', 'Padaria', 'iFood', 'Restaurante', 'Feira', 'Açougue', 'Lanche',
    ]],
    'transporte' => ['peso' => 14, 'desc' => [
        'Uber', '99', 'Gasolina', 'Estacionamento', 'Pedágio', 'Manutenção do carro',
    ]],
    'compras' => ['peso' => 12, 'desc' => [
        'Roupa', 'Mercado Livre', 'Farmácia', 'Presente', 'Calçado', 'Eletrônico',
    ]],
    'lazer' => ['peso' => 9, 'desc' => [
        'Cinema', 'Netflix', 'Spotify', 'Bar com amigos', 'Show', 'Streaming',
    ]],
    'saude' => ['peso' => 7, 'desc' => [
        'Plano de saúde', 'Farmácia', 'Consulta médica', 'Academia', 'Exame',
    ]],
];

/**
 * Distribui $alvo entre as categorias de $pools (por peso), quebra cada fatia em 1-3
 * lançamentos (valores aleatórios que somam a fatia exata — corte tipo Dirichlet: pesos
 * aleatórios normalizados, não faixa fixa por item) e corrige o arredondamento de centavos no
 * último lançamento do mês, pra soma final bater com $alvo centavo a centavo.
 */
function gerarDespesasDoMes(array $pools, float $alvo, DateTime $mesRef): array
{
    $pesoTotal = array_sum(array_column($pools, 'peso'));
    $itens = [];
    $diasNoMes = (int) $mesRef->format('t');

    foreach ($pools as $cat => $p) {
        $fatia = round($alvo * $p['peso'] / $pesoTotal, 2);
        $n = mt_rand(1, 3);

        $pesos = [];
        for ($i = 0; $i < $n; $i++) { $pesos[] = mt_rand(10, 100); }
        $somaPesos = array_sum($pesos);

        $somaFatia = 0.0;
        foreach ($pesos as $i => $w) {
            $valor = ($i === $n - 1) ? round($fatia - $somaFatia, 2) : round($fatia * $w / $somaPesos, 2);
            $somaFatia += $valor;
            if ($valor <= 0) continue; // fatia pequena demais pro n sorteado — pula item residual

            $dia = mt_rand(1, $diasNoMes);
            $hora = sprintf('%02d:%02d:00', mt_rand(7, 22), mt_rand(0, 59));
            $itens[] = [
                'tipo' => 'despesa', 'categoria' => $cat,
                'descricao' => $p['desc'][array_rand($p['desc'])],
                'valor' => $valor,
                'data_hora' => $mesRef->format('Y-m-') . sprintf('%02d', $dia) . ' ' . $hora,
                'origem' => mt_rand(1, 100) <= 30 ? 'foto' : 'manual',
            ];
        }
    }

    // Corrige o arredondamento acumulado dos round() acima no último item gerado, pra soma do
    // mês bater exatamente com $alvo (até o centavo).
    $somaReal = array_sum(array_column($itens, 'valor'));
    $diff = round($alvo - $somaReal, 2);
    if ($diff !== 0.0 && !empty($itens)) {
        $ultimo = array_key_last($itens);
        $itens[$ultimo]['valor'] = round($itens[$ultimo]['valor'] + $diff, 2);
    }

    return $itens;
}

// ---------------------------------------------------------------------------------------
// Gera os $meses meses, do mais antigo pro mais recente
// ---------------------------------------------------------------------------------------

$lancamentos = [];
$totaisPorMes = [];
$cursor = clone $inicioJanela;
for ($m = 0; $m < $meses; $m++) {
    $alvoGasto = round(mt_rand((int) ($gastoMin * 100), (int) ($gastoMax * 100)) / 100, 2);
    $itensMes = gerarDespesasDoMes($pools, $alvoGasto, $cursor);
    $lancamentos = array_merge($lancamentos, $itensMes);

    // Salário ±5% em torno da média pedida, sempre dia 5.
    $salario = round($salarioMedio * (mt_rand(95, 105) / 100), 2);
    $lancamentos[] = [
        'tipo' => 'receita', 'categoria' => 'salario', 'descricao' => 'Salário',
        'valor' => $salario, 'data_hora' => $cursor->format('Y-m-05') . ' 09:00:00', 'origem' => 'manual',
    ];

    $totaisPorMes[$cursor->format('m/Y')] = ['gasto' => $alvoGasto, 'salario' => $salario];
    $cursor->modify('+1 month');
}

usort($lancamentos, fn($a, $b) => strcmp($a['data_hora'], $b['data_hora']));

$somaGastos = array_sum(array_column($totaisPorMes, 'gasto'));
$somaSalarios = array_sum(array_column($totaisPorMes, 'salario'));

echo "Gerados " . count($lancamentos) . " lançamentos fictícios em {$meses} meses (de "
    . $inicioJanela->format('m/Y') . " até " . $fimJanelaLabel->format('m/Y') . ").\n";
printf("Salário médio: R$ %.2f (pedido: R$ %.2f) — gasto médio: R$ %.2f (faixa R$ %.2f–%.2f)\n",
    $somaSalarios / $meses, $salarioMedio, $somaGastos / $meses, $gastoMin, $gastoMax);
echo str_repeat('-', 78) . "\n";

echo "Resumo por mês:\n";
foreach ($totaisPorMes as $mesLabel => $t) {
    printf("  %s | salário R$ %9.2f | gastos R$ %9.2f | saldo R$ %9.2f\n",
        $mesLabel, $t['salario'], $t['gasto'], $t['salario'] - $t['gasto']);
}
echo str_repeat('-', 78) . "\n";

if (!$aplicar) {
    echo "Amostra (10 primeiros lançamentos):\n";
    foreach (array_slice($lancamentos, 0, 10) as $l) {
        printf("  %s | %-12s | %-25s | R$ %8.2f | %s\n", $l['data_hora'], $l['categoria'], $l['descricao'], $l['valor'], $l['tipo']);
    }
    echo "\nRode com --aplicar pra gravar de verdade.\n";
    exit(0);
}

// Fase 1 (PF/PJ) — todo lançamento agora só aparece em alguma tela se tiver perfil_id/conta_id
// (toda consulta filtra por isso); resolve (ou cria, igual o primeiro acesso de verdade faria)
// o perfil "Pessoal" + conta "Carteira" do usuário antes de gravar qualquer coisa.
$perfil = \App\Services\Fixa\PerfilService::perfilAtivo($db, (int) $usuario['id']);
$contas = \App\Services\Fixa\PerfilService::contasDoPerfil($db, (int) $perfil['id']);
$contaId = (int) ($contas[0]['id'] ?? 0);

// Garante que a categoria "salario" existe no PERFIL (as 6 de despesa já são semeadas
// automaticamente na primeira leitura de categoriasDoPerfil(), ver PerfilService — mas nunca
// inclui uma de receita; sem ela, o app mostraria o lançamento com o fallback cinza "salario"
// em vez de um chip de verdade).
$temSalario = $db->prepare("SELECT 1 FROM financeiro_pessoal_categorias WHERE perfil_id = ? AND chave = 'salario'");
$temSalario->execute([$perfil['id']]);
if (!$temSalario->fetchColumn()) {
    $pos = $db->prepare("SELECT COALESCE(MAX(posicao), -1) + 1 FROM financeiro_pessoal_categorias WHERE perfil_id = ?");
    $pos->execute([$perfil['id']]);
    $db->prepare(
        "INSERT INTO financeiro_pessoal_categorias (usuario_id, perfil_id, chave, nome, tipo, cor, posicao) VALUES (?, ?, 'salario', 'Salário', 'receita', '#16a34a', ?)"
    )->execute([$usuario['id'], $perfil['id'], (int) $pos->fetchColumn()]);
    echo "Categoria \"Salário\" criada no perfil (não existia ainda).\n";
}

$stmt = $db->prepare(
    "INSERT INTO financeiro_pessoal_lancamentos (usuario_id, perfil_id, conta_id, tipo, categoria, descricao, valor, data_hora, origem)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$idsCriados = [];
foreach ($lancamentos as $l) {
    $stmt->execute([$usuario['id'], $perfil['id'], $contaId, $l['tipo'], $l['categoria'], $l['descricao'], $l['valor'], $l['data_hora'], $l['origem']]);
    $idsCriados[] = (int) $db->lastInsertId();
}

echo "Gravado! " . count($idsCriados) . " lançamentos criados pro usuário {$usuario['id']}.\n";
echo "\nPra desfazer:\nDELETE FROM financeiro_pessoal_lancamentos WHERE id IN (" . implode(',', $idsCriados) . ");\n";
