<?php
/**
 * Popula financeiro_pessoal_lancamentos com dados fictícios pra um usuário de teste — só pra
 * dar vida ao Dashboard/gráficos do Financeiro Pessoal (ver migration 075) antes de ter uso
 * real o bastante pra validar visualmente.
 *
 * Por padrão roda em modo SIMULAÇÃO (não grava nada, só mostra o que faria). Pra gravar:
 *   php scripts/seed_financeiro_pessoal_demo.php --aplicar
 *
 * Opções:
 *   --usuario=ID   força o id do usuário (ignora a resolução por nome/empresa abaixo)
 *   --empresa=ID   empresa onde buscar o usuário (padrão: busca por nome_fantasia LIKE
 *                  '%tvservice%' / '%tv service%' — a empresa piloto, TV Service)
 *   --nome=TEXTO   nome do usuário dentro dessa empresa (padrão: 'sergio')
 *
 * Gera ~2 meses de lançamentos (mês anterior completo + mês atual até hoje), pra o Dashboard
 * já mostrar variação mês a mês, gráfico diário com dado espalhado, e quebra por categoria.
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

$argOpt = function (string $nome, $default) use ($argv) {
    foreach ($argv as $a) {
        if (str_starts_with($a, "--{$nome}=")) return substr($a, strlen($nome) + 3);
    }
    return $default;
};
$usuarioArg = $argOpt('usuario', null);
$empresaArg = $argOpt('empresa', null);
$nomeArg    = $argOpt('nome', 'sergio');

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
// Pools de dados fictícios por categoria — pesos aproximam um gasto pessoal real
// ---------------------------------------------------------------------------------------

$pools = [
    'alimentacao' => ['peso' => 30, 'faixa' => [15, 180], 'desc' => [
        'Supermercado Dia', 'Supermercado Extra', 'Padaria', 'iFood', 'Restaurante', 'Feira',
        'Açougue', 'Lanche', 'Café da manhã', 'Pizza',
    ]],
    'transporte' => ['peso' => 15, 'faixa' => [8, 120], 'desc' => [
        'Uber', '99', 'Gasolina', 'Estacionamento', 'Pedágio', 'Ônibus', 'Manutenção do carro',
    ]],
    'lazer' => ['peso' => 15, 'faixa' => [20, 250], 'desc' => [
        'Cinema', 'Netflix', 'Spotify', 'Bar com amigos', 'Show', 'Streaming', 'Parque',
    ]],
    'compras' => ['peso' => 20, 'faixa' => [30, 400], 'desc' => [
        'Roupa', 'Mercado Livre', 'Amazon', 'Farmácia', 'Presente', 'Calçado', 'Eletrônico',
    ]],
    'moradia' => ['peso' => 10, 'faixa' => [50, 600], 'desc' => [
        'Conta de luz', 'Conta de água', 'Internet', 'Condomínio', 'Gás', 'Material de limpeza',
    ]],
    'saude' => ['peso' => 5, 'faixa' => [25, 300], 'desc' => [
        'Farmácia', 'Consulta médica', 'Plano odontológico', 'Academia', 'Exame',
    ]],
    'outros' => ['peso' => 5, 'faixa' => [10, 150], 'desc' => [
        'Diversos', 'Pix pro irmão', 'Doação', 'Correios',
    ]],
];

$receitas = [
    ['desc' => 'Salário', 'faixa' => [3500, 3500]],
    ['desc' => 'Freela', 'faixa' => [200, 900]],
];

function sorteiaCategoria(array $pools): string
{
    $total = array_sum(array_column($pools, 'peso'));
    $r = mt_rand(1, $total);
    $acc = 0;
    foreach ($pools as $chave => $p) {
        $acc += $p['peso'];
        if ($r <= $acc) return $chave;
    }
    return array_key_first($pools);
}

// ---------------------------------------------------------------------------------------
// Gera ~2 meses: mês anterior inteiro + mês atual até hoje
// ---------------------------------------------------------------------------------------

$hoje = new DateTime();
$inicioJanela = (new DateTime('first day of last month'))->setTime(0, 0);
$fimJanela = $hoje;

$lancamentos = [];
$dataCursor = clone $inicioJanela;
while ($dataCursor <= $fimJanela) {
    // 1 a 3 gastos por dia, com chance de dia vazio (fim de semana calmo, etc.)
    $qtdHoje = mt_rand(0, 3);
    for ($i = 0; $i < $qtdHoje; $i++) {
        $cat = sorteiaCategoria($pools);
        $p = $pools[$cat];
        $valor = mt_rand($p['faixa'][0] * 100, $p['faixa'][1] * 100) / 100;
        $desc = $p['desc'][array_rand($p['desc'])];
        $hora = sprintf('%02d:%02d:00', mt_rand(7, 22), mt_rand(0, 59));
        $lancamentos[] = [
            'tipo' => 'despesa', 'categoria' => $cat, 'descricao' => $desc, 'valor' => $valor,
            'data_hora' => $dataCursor->format('Y-m-d') . ' ' . $hora,
            'origem' => mt_rand(1, 100) <= 30 ? 'foto' : 'manual',
        ];
    }
    // Salário sempre dia 5, freela ocasional
    if ($dataCursor->format('d') === '05') {
        $lancamentos[] = [
            'tipo' => 'receita', 'categoria' => 'outros', 'descricao' => 'Salário',
            'valor' => 3500.00, 'data_hora' => $dataCursor->format('Y-m-d') . ' 09:00:00', 'origem' => 'manual',
        ];
    }
    if (mt_rand(1, 100) <= 8) {
        $valor = mt_rand(20000, 90000) / 100;
        $lancamentos[] = [
            'tipo' => 'receita', 'categoria' => 'outros', 'descricao' => 'Freela',
            'valor' => $valor, 'data_hora' => $dataCursor->format('Y-m-d') . ' 18:00:00', 'origem' => 'manual',
        ];
    }
    $dataCursor->modify('+1 day');
}

echo "Gerados " . count($lancamentos) . " lançamentos fictícios (de " . $inicioJanela->format('d/m/Y') . " até " . $fimJanela->format('d/m/Y') . ").\n";
echo str_repeat('-', 78) . "\n";

if (!$aplicar) {
    echo "Amostra (10 primeiros):\n";
    foreach (array_slice($lancamentos, 0, 10) as $l) {
        printf("  %s | %-10s | %-25s | R$ %8.2f | %s\n", $l['data_hora'], $l['categoria'], $l['descricao'], $l['valor'], $l['tipo']);
    }
    echo "\nRode com --aplicar pra gravar de verdade.\n";
    exit(0);
}

$stmt = $db->prepare(
    "INSERT INTO financeiro_pessoal_lancamentos (usuario_id, tipo, categoria, descricao, valor, data_hora, origem)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$idsCriados = [];
foreach ($lancamentos as $l) {
    $stmt->execute([$usuario['id'], $l['tipo'], $l['categoria'], $l['descricao'], $l['valor'], $l['data_hora'], $l['origem']]);
    $idsCriados[] = (int) $db->lastInsertId();
}

echo "Gravado! " . count($idsCriados) . " lançamentos criados pro usuário {$usuario['id']}.\n";
echo "\nPra desfazer:\nDELETE FROM financeiro_pessoal_lancamentos WHERE id IN (" . implode(',', $idsCriados) . ");\n";
