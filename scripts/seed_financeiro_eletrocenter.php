<?php
/**
 * Popula a empresa fictícia "Eletrocenter" (ver scripts/seed_empresa_eletrocenter.php — precisa
 * já ter sido rodado com --aplicar antes deste; e scripts/limpar_dados_eletrocenter.php se for
 * rodar de novo em cima de dados já gerados antes) com clientes, produtos e ordens de serviço
 * dos últimos N meses (padrão 3), dimensionados pra que o faturamento (OS entregues e pagas) de
 * cada mês caia numa faixa alvo (padrão R$ 60.000–120.000) — pensado pra deixar Dashboard,
 * Fluxo de Caixa e Relatórios com números bons pra print de tela na landing page. Nenhum dado
 * real: nomes, CPF, telefone, e-mail e defeitos são todos gerados.
 *
 * Os clientes (padrão 450) nascem com `criado_em` distribuído nesses mesmos N meses, em média
 * `clientes/meses` novos cadastros por mês (padrão 150/mês) — não é derivado da data da OS mais
 * antiga do cliente, é uma linha do tempo própria, pra bater com o mesmo período das OS/receita.
 *
 * Diferente de scripts/seed_dados_demo.php (que deliberadamente não mexe no Financeiro), este
 * script TAMBÉM grava em fin_lancamentos — uma linha de receita por OS entregue/paga, espelhando
 * o que OrdemServicoController::fechar() grava de verdade num fechamento com pagamento, mais um
 * punhado de despesas mensais (aluguel, salários, fornecedor) pra o Fluxo de Caixa não parecer
 * só uma parede de receita.
 *
 * Por padrão roda em modo SIMULAÇÃO (não grava nada, só mostra o que faria). Pra gravar:
 *   php scripts/seed_financeiro_eletrocenter.php --aplicar
 *
 * Opções:
 *   --meses=3         quantos meses (incluindo o atual, que fica parcial/pro-rata) gerar
 *   --clientes=450    total de clientes fictícios, distribuídos igualmente pelos N meses
 *   --min-mes=60000   piso do faturamento alvo de cada mês
 *   --max-mes=120000  teto do faturamento alvo de cada mês
 *   --empresa=ID       força o id da empresa (padrão: busca por nome_fantasia/razao_social LIKE '%Eletrocenter%')
 *
 * Pra apagar depois, use scripts/limpar_dados_eletrocenter.php --aplicar (apaga tudo que este
 * script cria e mais nada, sem precisar guardar faixa de número/tag manualmente).
 */

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

// ---------------------------------------------------------------------------------------
// Args
// ---------------------------------------------------------------------------------------

$aplicar = in_array('--aplicar', $argv, true);

$argOpt = function (string $nome, $default) use ($argv) {
    foreach ($argv as $a) {
        if (str_starts_with($a, "--{$nome}=")) return substr($a, strlen($nome) + 3);
    }
    return $default;
};

$numMeses     = max(1, (int) $argOpt('meses', 3));
$totalClientes = max(1, (int) $argOpt('clientes', 450));
$minMes     = max(0.0, (float) $argOpt('min-mes', 60000));
$maxMes     = max($minMes, (float) $argOpt('max-mes', 120000));
$empresaArg = $argOpt('empresa', null);

$db = App\Core\DB::pdo();

echo ($aplicar ? "MODO APLICAR — vai gravar de verdade no banco.\n" : "MODO SIMULAÇÃO — nada será gravado (rode com --aplicar pra gravar de verdade).\n");
echo str_repeat('-', 78) . "\n";

// ---------------------------------------------------------------------------------------
// Resolve empresa
// ---------------------------------------------------------------------------------------

if ($empresaArg !== null) {
    $stmt = $db->prepare("SELECT id, nome_fantasia, razao_social FROM empresas WHERE id = ?");
    $stmt->execute([(int) $empresaArg]);
    $empresa = $stmt->fetch();
    if (!$empresa) { fwrite(STDERR, "Empresa #{$empresaArg} não encontrada.\n"); exit(1); }
} else {
    $stmt = $db->query(
        "SELECT id, nome_fantasia, razao_social FROM empresas
         WHERE nome_fantasia LIKE '%Eletrocenter%' OR razao_social LIKE '%Eletrocenter%'"
    );
    $candidatos = $stmt->fetchAll();
    if (count($candidatos) === 0) {
        fwrite(STDERR, "Empresa 'Eletrocenter' não encontrada. Rode primeiro:\n  php scripts/seed_empresa_eletrocenter.php --aplicar\n");
        exit(1);
    }
    if (count($candidatos) > 1) {
        fwrite(STDERR, "Mais de uma empresa bateu com 'Eletrocenter' — escolha uma com --empresa=ID:\n");
        foreach ($candidatos as $c) fwrite(STDERR, "  #{$c['id']} — {$c['nome_fantasia']} ({$c['razao_social']})\n");
        exit(1);
    }
    $empresa = $candidatos[0];
}
$eid = (int) $empresa['id'];
echo "Empresa alvo: #{$eid} — {$empresa['nome_fantasia']} ({$empresa['razao_social']})\n";

// ---------------------------------------------------------------------------------------
// Contexto existente da empresa
// ---------------------------------------------------------------------------------------

$stmt = $db->prepare("SELECT id, tipo FROM os_status WHERE empresa_id = ?");
$stmt->execute([$eid]);
$statusPorTipo = [];
foreach ($stmt->fetchAll() as $r) $statusPorTipo[$r['tipo']][] = (int) $r['id'];
if (!$statusPorTipo || empty($statusPorTipo['entregue'])) {
    fwrite(STDERR, "Empresa não tem os_status com tipo='entregue' — rode seed_empresa_eletrocenter.php --aplicar primeiro.\n");
    exit(1);
}

$stmt = $db->prepare("SELECT id, nome FROM categorias_equipamento WHERE empresa_id = ?");
$stmt->execute([$eid]);
$categoriaEquipId = [];
foreach ($stmt->fetchAll() as $r) $categoriaEquipId[$r['nome']] = (int) $r['id'];

$stmt = $db->prepare("SELECT id FROM usuarios WHERE empresa_id = ? AND ativo = 1 ORDER BY (perfil='admin') DESC, id LIMIT 1");
$stmt->execute([$eid]);
$usuarioId = $stmt->fetchColumn() ?: null;
$usuarioId = $usuarioId ? (int) $usuarioId : null;

$stmt = $db->prepare("SELECT id FROM usuarios WHERE empresa_id = ? AND ativo = 1 AND (perfil = 'tecnico' OR atende_os = 1)");
$stmt->execute([$eid]);
$tecnicos = array_column($stmt->fetchAll(), 'id');

$stmt = $db->prepare("SELECT chave, valor FROM configuracoes WHERE empresa_id = ? AND chave = 'os_digitos'");
$stmt->execute([$eid]);
$row = $stmt->fetch();
$digitos = $row ? (int) $row['valor'] : 6;

$stmt = $db->prepare("SELECT MAX(CAST(numero AS UNSIGNED)) FROM ordens_servico WHERE empresa_id = ?");
$stmt->execute([$eid]);
$inicio = max(1, (int) $stmt->fetchColumn() + 1);

$temFechadaSemReceita = (bool) $db->query("SHOW COLUMNS FROM ordens_servico LIKE 'fechada_sem_receita'")->fetch();

// Conta financeira e categorias (receita/despesa) — reaproveita o que seed_empresa_eletrocenter.php já criou.
$stmt = $db->prepare("SELECT id FROM fin_contas WHERE empresa_id = ? ORDER BY id LIMIT 1");
$stmt->execute([$eid]);
$contaId = $stmt->fetchColumn();
if (!$contaId) { fwrite(STDERR, "Empresa não tem fin_contas — rode seed_empresa_eletrocenter.php --aplicar primeiro.\n"); exit(1); }
$contaId = (int) $contaId;

$stmt = $db->prepare("SELECT id FROM fin_categorias WHERE empresa_id = ? AND tipo = 'receita' ORDER BY id LIMIT 1");
$stmt->execute([$eid]);
$catReceitaId = $stmt->fetchColumn();
if (!$catReceitaId) { fwrite(STDERR, "Empresa não tem fin_categorias de receita — rode seed_empresa_eletrocenter.php --aplicar primeiro.\n"); exit(1); }
$catReceitaId = (int) $catReceitaId;

$stmt = $db->prepare("SELECT id FROM fin_categorias WHERE empresa_id = ? AND tipo = 'despesa' AND nome = 'Despesas Operacionais'");
$stmt->execute([$eid]);
$catDespesaId = $stmt->fetchColumn();
$catDespesaExiste = (bool) $catDespesaId;
$catDespesaId = $catDespesaId ? (int) $catDespesaId : null;

echo "Técnicos disponíveis: " . count($tecnicos) . " | Conta financeira: #{$contaId} | Categoria receita: #{$catReceitaId}\n";
echo "Meses a gerar: {$numMeses} | Faturamento alvo por mês: R$ " . number_format($minMes, 2, ',', '.') . " a R$ " . number_format($maxMes, 2, ',', '.') . "\n";
echo str_repeat('-', 78) . "\n";

// ---------------------------------------------------------------------------------------
// Pools de dados fictícios (mesmo padrão de scripts/seed_dados_demo.php)
// ---------------------------------------------------------------------------------------

$NOMES_M = ['João','Pedro','Lucas','Gabriel','Matheus','Rafael','Carlos','Marcos','Felipe','Bruno',
    'Eduardo','Rodrigo','Diego','Thiago','André','Fernando','Ricardo','Vinícius','Gustavo','Leonardo',
    'Alexandre','Daniel','Paulo','Antônio','José','Roberto','Sérgio','Cláudio','Anderson','Marcelo'];
$NOMES_F = ['Maria','Ana','Juliana','Fernanda','Camila','Beatriz','Larissa','Patrícia','Aline','Bruna',
    'Carla','Débora','Amanda','Letícia','Vanessa','Priscila','Renata','Simone','Tatiane','Cristina',
    'Adriana','Sandra','Mônica','Luciana','Rosana','Viviane','Elaine','Gisele','Kelly','Michele'];
$SOBRENOMES = ['Silva','Souza','Oliveira','Santos','Pereira','Costa','Rodrigues','Almeida','Nascimento',
    'Lima','Araújo','Fernandes','Carvalho','Gomes','Martins','Rocha','Ribeiro','Alves','Monteiro','Cardoso',
    'Teixeira','Moreira','Correia','Barbosa','Pinto','Dias','Nunes','Freitas','Machado','Vieira'];

function nomeAleatorio(array $NOMES_M, array $NOMES_F, array $SOBRENOMES): string
{
    $primeiro = mt_rand(0, 1) ? $NOMES_M[array_rand($NOMES_M)] : $NOMES_F[array_rand($NOMES_F)];
    $sobrenome1 = $SOBRENOMES[array_rand($SOBRENOMES)];
    $sobrenome2 = $SOBRENOMES[array_rand($SOBRENOMES)];
    return $sobrenome1 === $sobrenome2 ? "{$primeiro} {$sobrenome1}" : "{$primeiro} {$sobrenome1} {$sobrenome2}";
}

function weightedPick(array $pesos): string
{
    $total = array_sum($pesos);
    $r = mt_rand(1, max(1, $total));
    foreach ($pesos as $chave => $peso) {
        if ($r <= $peso) return (string) $chave;
        $r -= $peso;
    }
    return (string) array_key_first($pesos);
}

function precoEm(float $min, float $max): float
{
    return round(mt_rand((int) ($min * 100), (int) ($max * 100)) / 100, 2);
}

// Categorias de equipamento (nomes precisam bater com categorias_equipamento já semeada).
$CATEGORIAS = [
    'Celular/Smartphone' => [
        'peso' => 30,
        'aparelhos' => [['Samsung','Galaxy A54'],['Samsung','Galaxy S23'],['Motorola','Moto G84'],
            ['Apple','iPhone 12'],['Apple','iPhone 13'],['Xiaomi','Redmi Note 12']],
        'defeitos' => ['Tela quebrada após queda','Não liga mais','Bateria não segura carga','Molhou e parou de funcionar',
            'Alto-falante sem som','Não carrega, conector solto','Reiniciando sozinho'],
        'servicos' => [['Troca de tela',280,650],['Troca de bateria',90,180],['Troca de conector de carga',80,150],
            ['Reparo de placa (curto de energia)',150,400],['Diagnóstico técnico',40,60]],
        'pecas' => [['Tela Samsung Galaxy A54',320,0.68],['Tela iPhone 12',420,0.70],['Bateria Motorola Moto G84',95,0.62],
            ['Flat de carga USB-C',45,0.55],['Alto-falante genérico',35,0.50]],
        'acessorios' => ['Carregador','Capinha','Sem acessórios','Carregador e fone'],
    ],
    'Notebook' => [
        'peso' => 18,
        'aparelhos' => [['Dell','Inspiron 15'],['Lenovo','Ideapad 3'],['Acer','Aspire 5'],['Samsung','Book X30'],['HP','Pavilion 14']],
        'defeitos' => ['Muito lento, travando','Não liga','Tela quebrada','Superaquecendo e desligando sozinho',
            'HD com barulho estranho','Não reconhece a bateria'],
        'servicos' => [['Formatação com backup',120,220],['Limpeza interna (troca de pasta térmica)',90,150],
            ['Troca de tela',350,700],['Upgrade de memória RAM',80,180],['Troca de HD para SSD',150,350]],
        'pecas' => [['SSD 240GB',180,0.65],['Memória RAM 8GB DDR4',150,0.68],['Tela Notebook 15.6"',380,0.66],
            ['Cooler/ventoinha',70,0.50]],
        'acessorios' => ['Carregador','Mochila','Sem acessórios'],
    ],
    'Televisão' => [
        'peso' => 16,
        'aparelhos' => [['Samsung','Smart TV 50" Crystal UHD'],['LG','Smart TV 43" UHD'],['AOC','Smart TV 32"'],['Philco','Smart TV 55"']],
        'defeitos' => ['Não liga, luz do standby não acende','Tela com listras coloridas','Sem som','Reiniciando sozinha','Tela quebrada'],
        'servicos' => [['Troca de fonte',150,320],['Troca de placa principal',250,550],['Troca da tela',600,1400],
            ['Reparo de backlight (LEDs)',180,380]],
        'pecas' => [['Fonte TV LED 50"',180,0.60],['Placa principal TV (genérica)',320,0.65],['Kit LED backlight',150,0.55]],
        'acessorios' => ['Controle remoto','Sem acessórios'],
    ],
    'Geladeira/Freezer' => [
        'peso' => 10,
        'aparelhos' => [['Brastemp','Frost Free Duplex'],['Consul','Frost Free 340L'],['Electrolux','DF44']],
        'defeitos' => ['Não está gelando','Barulho alto no compressor','Vazando água por baixo','Motor não liga'],
        'servicos' => [['Troca de compressor',350,650],['Troca de termostato',90,180],['Reparo no sistema de degelo',180,380]],
        'pecas' => [['Compressor 1/4HP',420,0.70],['Termostato Geladeira',95,0.55],['Resistência de degelo',110,0.55]],
        'acessorios' => ['Sem acessórios'],
    ],
    'Máquina de Lavar' => [
        'peso' => 10,
        'aparelhos' => [['Brastemp','BWK11'],['Consul','CWH12'],['Electrolux','LAC12']],
        'defeitos' => ['Não centrifuga','Vazando água','Não liga','Fazendo muito barulho na centrifugação'],
        'servicos' => [['Troca de resistência',120,220],['Troca de rolamento do tambor',250,480],['Troca de placa eletrônica',280,550]],
        'pecas' => [['Resistência Máquina de Lavar',130,0.58],['Kit rolamento tambor',180,0.60],['Placa eletrônica (genérica)',320,0.65]],
        'acessorios' => ['Mangueira de saída','Sem acessórios'],
    ],
    'Ar Condicionado' => [
        'peso' => 8,
        'aparelhos' => [['Springer Midea','Split 9000 BTUs'],['Samsung','Split 12000 BTUs'],['LG','Split Dual Inverter']],
        'defeitos' => ['Não está gelando','Vazando água na parede interna','Gás baixo (perdendo eficiência)'],
        'servicos' => [['Recarga de gás',180,320],['Higienização completa',120,200],['Troca de placa eletrônica',280,520]],
        'pecas' => [['Placa eletrônica evaporadora',290,0.60],['Gás refrigerante R410 (carga)',150,0.55]],
        'acessorios' => ['Controle remoto','Sem acessórios'],
    ],
    'Videogame' => [
        'peso' => 8,
        'aparelhos' => [['Sony','PlayStation 4 Slim'],['Sony','PlayStation 5'],['Microsoft','Xbox Series S']],
        'defeitos' => ['Superaquecendo e desligando','Não lê os discos','Não liga'],
        'servicos' => [['Troca de pasta térmica e limpeza',90,150],['Troca de leitor de disco',150,280],['Reparo de HDMI',120,220]],
        'pecas' => [['Leitor de disco (genérico)',160,0.55],['Fonte de videogame',110,0.55]],
        'acessorios' => ['Controle','Cabo HDMI','Sem acessórios'],
    ],
];

$CORES = ['Preto','Branco','Prata','Cinza espacial','Azul','Vermelho'];
$CIDADES_EXTRA = [['Campinas','SP'],['Sorocaba','SP'],['Jundiaí','SP'],['Osasco','SP'],['Guarulhos','SP']];

// ---------------------------------------------------------------------------------------
// Janela de meses: do mais antigo (índice numMeses-1) até o atual (índice 0, parcial/pro-rata)
// ---------------------------------------------------------------------------------------

$agora = time();
$meses = [];
for ($m = $numMeses - 1; $m >= 0; $m--) {
    $ref = strtotime("-{$m} months", $agora);
    $ano = (int) date('Y', $ref);
    $mesNum = (int) date('n', $ref);
    $inicioMes = mktime(0, 0, 0, $mesNum, 1, $ano);
    $fimMes = mktime(0, 0, 0, $mesNum + 1, 1, $ano); // exclusivo
    $fimEfetivo = ($m === 0) ? min($fimMes, $agora + 1) : $fimMes;
    $fracao = max(0.05, ($fimEfetivo - $inicioMes) / ($fimMes - $inicioMes));
    $alvo = round(mt_rand((int) ($minMes * 100), (int) ($maxMes * 100)) / 100 * $fracao, 2);
    $meses[] = [
        'label' => date('m/Y', $inicioMes),
        'inicio' => $inicioMes,
        'fim' => $fimEfetivo,
        'atual' => ($m === 0),
        'alvo' => $alvo,
    ];
}

echo "Janela de meses:\n";
foreach ($meses as $mm) {
    echo "  - {$mm['label']}" . ($mm['atual'] ? ' (parcial, até hoje)' : '') . ": alvo R$ " . number_format($mm['alvo'], 2, ',', '.') . "\n";
}
echo str_repeat('-', 78) . "\n";

// ---------------------------------------------------------------------------------------
// Timestamp aleatório dentro de uma janela, em horário comercial, pulando domingo
// ---------------------------------------------------------------------------------------

function tsAleatorioNaJanela(int $inicio, int $fim): int
{
    $fim = max($fim, $inicio + 3600);
    $ts = mt_rand($inicio, $fim - 1);
    $hora = mt_rand(8, 18);
    $min = mt_rand(0, 59);
    $ts = mktime($hora, $min, 0, (int) date('n', $ts), (int) date('j', $ts), (int) date('Y', $ts));
    if ((int) date('N', $ts) === 7) $ts += 86400; // domingo -> segunda
    return $ts;
}

// ---------------------------------------------------------------------------------------
// Monta um item de OS (categoria/aparelho/defeito/serviços/peças) já com valor calculado —
// mesma lógica de precificação de scripts/seed_dados_demo.php.
// ---------------------------------------------------------------------------------------

function montarItemOs(array $CATEGORIAS, array $produtoIdPorNome): array
{
    $catKeys = array_keys($CATEGORIAS);
    $catPesos = array_combine($catKeys, array_column($CATEGORIAS, 'peso'));
    $catNome = weightedPick($catPesos);
    $cat = $CATEGORIAS[$catNome];

    $itensServ = [];
    $qtdServ = mt_rand(1, count($cat['servicos']) > 1 ? 2 : 1);
    $servEscolhidos = (array) array_rand($cat['servicos'], min($qtdServ, count($cat['servicos'])));
    $subtotal = 0.0;
    foreach ($servEscolhidos as $si) {
        [$desc, $min, $max] = $cat['servicos'][$si];
        $valor = precoEm($min, $max);
        $itensServ[] = [$desc, $valor];
        $subtotal += $valor;
    }

    $itensPeca = [];
    if (mt_rand(1, 100) <= 65 && $cat['pecas']) {
        $qtdPeca = mt_rand(1, count($cat['pecas']) > 1 ? 2 : 1);
        $pecaEscolhidas = (array) array_rand($cat['pecas'], min($qtdPeca, count($cat['pecas'])));
        foreach ($pecaEscolhidas as $pi) {
            [$nomePeca] = $cat['pecas'][$pi];
            if (!isset($produtoIdPorNome[$nomePeca])) continue;
            $p = $produtoIdPorNome[$nomePeca];
            $itensPeca[] = [$nomePeca, $p['id'], $p['custo'], $p['venda']];
            $subtotal += $p['venda'];
        }
    }

    return [
        'catNome' => $catNome,
        'aparelho' => $cat['aparelhos'][array_rand($cat['aparelhos'])],
        'defeito' => $cat['defeitos'][array_rand($cat['defeitos'])],
        'acessorio' => $cat['acessorios'][array_rand($cat['acessorios'])],
        'itensServ' => $itensServ,
        'itensPeca' => $itensPeca,
        'valorTotal' => round($subtotal, 2),
    ];
}

// ---------------------------------------------------------------------------------------
// Pool de clientes (gerado em memória) — criado_em distribuído nos mesmos N meses das OS,
// em média totalClientes/numMeses cadastros novos por mês (ex.: 450/3 = 150/mês), com uma
// variação pequena por mês mas sempre somando o total exato (o último mês absorve o resto).
// ---------------------------------------------------------------------------------------

$mediaClientesPorMes = $totalClientes / $numMeses;
$clientesPorMes = [];
$restanteClientes = $totalClientes;
for ($i = 0; $i < $numMeses; $i++) {
    if ($i === $numMeses - 1) {
        $qtd = $restanteClientes; // último mês fecha a conta certinha
    } else {
        $variacao = $mediaClientesPorMes * (mt_rand(-15, 15) / 100);
        $qtd = max(1, min($restanteClientes - ($numMeses - $i - 1), (int) round($mediaClientesPorMes + $variacao)));
    }
    $clientesPorMes[] = $qtd;
    $restanteClientes -= $qtd;
}

$clientesPool = [];
foreach ($clientesPorMes as $mesIdx => $qtdNoMes) {
    for ($j = 0; $j < $qtdNoMes; $j++) {
        $nome = nomeAleatorio($NOMES_M, $NOMES_F, $SOBRENOMES);
        $ehLocal = mt_rand(1, 100) <= 85;
        [$cidade, $uf] = $ehLocal ? [null, null] : $CIDADES_EXTRA[array_rand($CIDADES_EXTRA)];
        $tel = '9' . mt_rand(6000, 9999) . '-' . mt_rand(1000, 9999);
        $slug = strtolower(str_replace(' ', '.', preg_replace('/[^a-zA-Z ]/', '', $nome)));
        $dominios = ['gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com.br'];
        $clientesPool[] = [
            'nome' => $nome,
            'telefone' => "(11) {$tel}",
            'email' => $slug . mt_rand(1, 999) . '@' . $dominios[array_rand($dominios)],
            'cpf' => sprintf('%03d.%03d.%03d-%02d', mt_rand(0, 999), mt_rand(0, 999), mt_rand(0, 999), mt_rand(0, 99)),
            'cidade' => $cidade,
            'uf' => $uf,
            'origem' => weightedPick(['balcao' => 35, 'whatsapp' => 30, 'indicacao' => 20, 'telefone' => 10, 'site' => 5]),
            'mesIdx' => $mesIdx,
        ];
    }
}

echo "Clientes fictícios a gerar: {$totalClientes} (";
foreach ($clientesPorMes as $mesIdx => $qtdNoMes) echo "{$meses[$mesIdx]['label']}: {$qtdNoMes}" . ($mesIdx < count($clientesPorMes) - 1 ? ', ' : '');
echo ")\n";
if (!$aplicar) {
    echo "\nPrévia (nada gravado) — 3 primeiros clientes:\n";
    for ($i = 0; $i < min(3, $totalClientes); $i++) {
        $c = $clientesPool[$i];
        echo "  - {$c['nome']} | {$c['telefone']} | {$c['email']} | CPF {$c['cpf']}\n";
    }
    echo "\nRode com --aplicar pra gravar de verdade.\n";
    exit(0);
}

// ---------------------------------------------------------------------------------------
// Grava
// ---------------------------------------------------------------------------------------

$db->beginTransaction();

try {
    // Clientes
    $stmtCli = $db->prepare(
        "INSERT INTO clientes (empresa_id, tipo, nome, cpf_cnpj, telefone, whatsapp, email, cidade, uf, origem, status, tags, criado_em)
         VALUES (?, 'pf', ?, ?, ?, ?, ?, ?, ?, ?, 'ativo', 'seed-demo', ?)"
    );
    $clienteIds = [];
    foreach ($clientesPool as $c) {
        $mesCliente = $meses[$c['mesIdx']];
        $criadoEm = date('Y-m-d H:i:s', tsAleatorioNaJanela($mesCliente['inicio'], $mesCliente['fim']));
        $stmtCli->execute([
            $eid, $c['nome'], $c['cpf'], $c['telefone'], $c['telefone'], $c['email'],
            $c['cidade'], $c['uf'], $c['origem'], $criadoEm,
        ]);
        $clienteIds[] = (int) $db->lastInsertId();
    }
    echo "Clientes gravados: " . count($clienteIds) . "\n";

    // Categoria de despesa, se ainda não existir
    if (!$catDespesaExiste) {
        $db->prepare("INSERT INTO fin_categorias (empresa_id, tipo, nome, cor) VALUES (?, 'despesa', 'Despesas Operacionais', '#dc3545')")
           ->execute([$eid]);
        $catDespesaId = (int) $db->lastInsertId();
        echo "Categoria de despesa criada: #{$catDespesaId}\n";
    }

    // Categorias de produto + produtos (peças)
    $stmt = $db->prepare("SELECT id, nome FROM categorias_produto WHERE empresa_id = ?");
    $stmt->execute([$eid]);
    $catProdId = [];
    foreach ($stmt->fetchAll() as $r) $catProdId[$r['nome']] = (int) $r['id'];

    $stmtCatProd = $db->prepare("INSERT INTO categorias_produto (empresa_id, nome) VALUES (?, ?)");
    $stmtProd = $db->prepare(
        "INSERT INTO produtos (empresa_id, categoria_id, codigo, nome, unidade, estoque_atual, estoque_minimo, valor_custo, valor_venda, ativo)
         VALUES (?, ?, ?, ?, 'un', ?, ?, ?, ?, 1)"
    );
    $produtoIdPorNome = [];
    $codigoSeq = 1;
    foreach ($CATEGORIAS as $catNome => $catDados) {
        $nomeCatProd = "DEMO: {$catNome}";
        if (!isset($catProdId[$nomeCatProd])) {
            $stmtCatProd->execute([$eid, $nomeCatProd]);
            $catProdId[$nomeCatProd] = (int) $db->lastInsertId();
        }
        foreach ($catDados['pecas'] as [$nomePeca, $venda, $custoPct]) {
            if (isset($produtoIdPorNome[$nomePeca])) continue;
            $codigo = 'DEMO-' . str_pad((string) $codigoSeq++, 4, '0', STR_PAD_LEFT);
            $custo = round($venda * $custoPct, 2);
            $stmtProd->execute([
                $eid, $catProdId[$nomeCatProd], $codigo, $nomePeca,
                mt_rand(8, 60), mt_rand(3, 8), $custo, $venda,
            ]);
            $produtoIdPorNome[$nomePeca] = ['id' => (int) $db->lastInsertId(), 'venda' => $venda, 'custo' => $custo];
        }
    }
    echo "Produtos (peças) gravados: " . count($produtoIdPorNome) . "\n";

    // Catálogo de serviços
    $stmt = $db->prepare("SELECT descricao FROM servicos_catalogo WHERE empresa_id = ?");
    $stmt->execute([$eid]);
    $servicosExistentes = array_column($stmt->fetchAll(), 'descricao');
    $stmtServCat = $db->prepare("INSERT INTO servicos_catalogo (empresa_id, descricao, valor_padrao, ativo) VALUES (?, ?, ?, 1)");
    foreach ($CATEGORIAS as $catDados) {
        foreach ($catDados['servicos'] as [$desc, $min, $max]) {
            $nomeServ = "DEMO: {$desc}";
            if (!in_array($nomeServ, $servicosExistentes, true)) {
                $stmtServCat->execute([$eid, $nomeServ, precoEm($min, $max)]);
                $servicosExistentes[] = $nomeServ;
            }
        }
    }
    echo "Serviços no catálogo: " . count($servicosExistentes) . "\n";
    echo str_repeat('-', 78) . "\n";

    // Prepared statements de OS
    $stmtEquip = $db->prepare(
        "INSERT INTO equipamentos (empresa_id, cliente_id, categoria_id, tipo, marca, modelo, numero_serie, imei, cor, voltagem, estado_entrada, descricao_defeito_cliente, acessorios)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'bivolt', ?, ?, ?)"
    );
    $stmtOs = $db->prepare(
        "INSERT INTO ordens_servico
         (empresa_id, numero, cliente_id, equipamento_id, status_id, tecnico_id, prioridade, tipo_servico,
          defeito_relatado, defeito_constatado, laudo_tecnico, solucao_aplicada,
          valor_diagnostico, desconto_percentual, desconto_valor, valor_total, valor_pago, situacao_pagamento,
          garantia_dias, garantia_ate, data_entrada, data_previsao, data_conclusao, data_entrega)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmtServ = $db->prepare(
        "INSERT INTO os_servicos (empresa_id, os_id, descricao, quantidade, valor_unitario, valor_total, tecnico_id, concluido)
         VALUES (?, ?, ?, 1, ?, ?, ?, ?)"
    );
    $stmtPeca = $db->prepare(
        "INSERT INTO os_pecas (empresa_id, os_id, produto_id, descricao, quantidade, valor_custo, valor_unitario, valor_total, estoque_baixado)
         VALUES (?, ?, ?, ?, 1, ?, ?, ?, 1)"
    );
    $sqlFecha = $temFechadaSemReceita ? "UPDATE ordens_servico SET fechada_sem_receita = ? WHERE id = ?" : null;
    $stmtFecha = $sqlFecha ? $db->prepare($sqlFecha) : null;
    $stmtFin = $db->prepare(
        "INSERT INTO fin_lancamentos (empresa_id, conta_id, categoria_id, os_id, cliente_id, usuario_id, tipo, descricao, valor,
          data_vencimento, data_pagamento, status, forma_pagamento, numero_documento)
         VALUES (?, ?, ?, ?, ?, ?, 'receita', ?, ?, ?, ?, 'pago', ?, 'SEED-ELETROCENTER')"
    );
    $stmtDespesa = $db->prepare(
        "INSERT INTO fin_lancamentos (empresa_id, conta_id, categoria_id, usuario_id, tipo, descricao, valor,
          data_vencimento, data_pagamento, status, forma_pagamento, numero_documento)
         VALUES (?, ?, ?, ?, 'despesa', ?, ?, ?, ?, 'pago', ?, 'SEED-ELETROCENTER')"
    );

    $formasPagamento = ['pix' => 45, 'cartao_credito' => 25, 'cartao_debito' => 15, 'dinheiro' => 15];

    $numeroSeq = $inicio;
    $totalOsGeradas = 0;
    $totalFaturado = 0.0;
    $totalDespesas = 0.0;
    $contagemStatus = [];

    $criaOs = function (
        array $item, int $clienteId, int $statusId, string $tipoStatus, int $ts, string $situacao, float $valorPago
    ) use (
        $db, $eid, &$numeroSeq, $digitos, $categoriaEquipId, $tecnicos, $CORES,
        $stmtEquip, $stmtOs, $stmtServ, $stmtPeca, $stmtFecha, $temFechadaSemReceita
    ): int {
        [$marca, $modelo] = $item['aparelho'];
        $ehCelularTablet = $item['catNome'] === 'Celular/Smartphone';
        $numSerie = strtoupper(substr(md5(uniqid((string) $numeroSeq, true)), 0, 10));
        $imei = $ehCelularTablet ? (string) mt_rand(100000000000000, 999999999999999) : null;

        $stmtEquip->execute([
            $eid, $clienteId, $categoriaEquipId[$item['catNome']] ?? null, $item['catNome'], $marca, $modelo,
            $numSerie, $imei, $CORES[array_rand($CORES)], weightedPick(['bom' => 50, 'regular' => 35, 'ruim' => 10, 'otimo' => 5]),
            $item['defeito'], $item['acessorio'],
        ]);
        $equipId = (int) $db->lastInsertId();

        $tipoServico = $tipoStatus === 'cancelada' ? weightedPick(['orcamento' => 70, 'conserto' => 30]) : 'conserto';
        $prazoDias = mt_rand(2, 6);
        $dataPrevisao = $ts + $prazoDias * 86400;
        $dataConclusao = $ts + mt_rand(1, max(1, $prazoDias)) * 86400;
        $dataEntrega = null;
        $garantiaAte = null;
        $defeitoConstatado = 'Confirmado: ' . lcfirst($item['defeito']);
        $solucaoAplicada = $item['itensServ'] ? implode('; ', array_column($item['itensServ'], 0)) : null;

        if ($tipoStatus === 'entregue') {
            $dataEntrega = $dataConclusao + mt_rand(0, 2) * 86400;
            $garantiaAte = date('Y-m-d', $dataEntrega + 90 * 86400);
        }

        $numero = str_pad($numeroSeq++, $digitos, '0', STR_PAD_LEFT);
        $tecnicoId = $tecnicos ? $tecnicos[array_rand($tecnicos)] : null;
        $prioridade = weightedPick(['normal' => 70, 'alta' => 15, 'baixa' => 10, 'urgente' => 5]);

        $stmtOs->execute([
            $eid, $numero, $clienteId, $equipId, $statusId, $tecnicoId, $prioridade, $tipoServico,
            $item['defeito'], $defeitoConstatado, null, $solucaoAplicada,
            0.0, 0, 0.0, $item['valorTotal'], $valorPago, $situacao,
            90, $garantiaAte, date('Y-m-d H:i:s', $ts), date('Y-m-d H:i:s', $dataPrevisao),
            date('Y-m-d H:i:s', $dataConclusao), $dataEntrega ? date('Y-m-d H:i:s', $dataEntrega) : null,
        ]);
        $osId = (int) $db->lastInsertId();

        if ($stmtFecha && $temFechadaSemReceita && $item['valorTotal'] <= 0) $stmtFecha->execute([1, $osId]);

        foreach ($item['itensServ'] as [$desc, $valor]) {
            $stmtServ->execute([$eid, $osId, $desc, $valor, $valor, $tecnicoId, 1]);
        }
        foreach ($item['itensPeca'] as [$nomePeca, $produtoId, $custo, $venda]) {
            $stmtPeca->execute([$eid, $osId, $produtoId, $nomePeca, $custo, $venda, $venda]);
        }

        return $osId;
    };

    foreach ($meses as $mm) {
        $acumulado = 0.0;
        $iteracoes = 0;
        $osDoMes = 0;

        echo "Mês {$mm['label']}" . ($mm['atual'] ? ' (parcial)' : '') . " — alvo R$ " . number_format($mm['alvo'], 2, ',', '.') . ":\n";

        // ---- OS que geram receita real (entregue + paga) ----
        while ($acumulado < $mm['alvo'] && $iteracoes < 800) {
            $iteracoes++;
            $item = montarItemOs($CATEGORIAS, $produtoIdPorNome);
            if ($item['valorTotal'] <= 0) continue; // nunca deveria acontecer, mas evita loop infinito

            $ts = tsAleatorioNaJanela($mm['inicio'], $mm['fim']);
            $clienteId = $clienteIds[array_rand($clienteIds)];

            $statusId = $statusPorTipo['entregue'][array_rand($statusPorTipo['entregue'])];
            $situacao = mt_rand(1, 100) <= 90 ? 'pago' : 'parcial';
            $valorPago = $situacao === 'pago' ? $item['valorTotal'] : round($item['valorTotal'] * mt_rand(50, 85) / 100, 2);

            $osId = $criaOs($item, $clienteId, $statusId, 'entregue', $ts, $situacao, $valorPago);

            if ($valorPago > 0) {
                $forma = weightedPick($formasPagamento);
                $dataPag = date('Y-m-d', min($ts + mt_rand(0, 2) * 86400, $mm['fim'] - 1));
                $stmtFin->execute([
                    $eid, $contaId, $catReceitaId, $osId, $clienteId, $usuarioId,
                    "Serviço técnico — OS {$item['catNome']}", $valorPago, $dataPag, $dataPag, $forma,
                ]);
                $acumulado += $valorPago;
                $totalFaturado += $valorPago;
            }

            $osDoMes++;
            $totalOsGeradas++;
            $contagemStatus['entregue'] = ($contagemStatus['entregue'] ?? 0) + 1;
        }
        if ($iteracoes >= 800) {
            echo "  (aviso: atingiu o limite de segurança de 800 OS neste mês sem alcançar o alvo)\n";
        }

        // ---- OS "pipeline" — não contam receita, só dão variedade às listas/kanban ----
        if ($mm['atual']) {
            $pesosPipeline = ['aberta' => 35, 'em_andamento' => 30, 'aguardando' => 25, 'concluida' => 10];
        } else {
            $pesosPipeline = ['cancelada' => 100];
        }
        $pesosPipeline = array_intersect_key($pesosPipeline, $statusPorTipo);
        $qtdPipeline = (int) round($osDoMes * (mt_rand(15, 35) / 100));
        for ($i = 0; $i < $qtdPipeline; $i++) {
            $tipoStatus = $pesosPipeline ? weightedPick($pesosPipeline) : array_key_first($statusPorTipo);
            if (!isset($statusPorTipo[$tipoStatus])) continue;
            $statusId = $statusPorTipo[$tipoStatus][array_rand($statusPorTipo[$tipoStatus])];
            $item = montarItemOs($CATEGORIAS, $produtoIdPorNome);
            $ts = tsAleatorioNaJanela($mm['inicio'], $mm['fim']);
            $clienteIdx = array_rand($clienteIds);
            $clienteId = $clienteIds[$clienteIdx];

            if ($tipoStatus === 'cancelada' && mt_rand(1, 100) <= 50) {
                $item['valorTotal'] = 0.0;
                $item['itensServ'] = [];
                $item['itensPeca'] = [];
            }
            $situacao = $item['valorTotal'] > 0 && $tipoStatus === 'cancelada' ? 'pago' : 'pendente';
            $valorPago = $situacao === 'pago' ? $item['valorTotal'] : 0.0;

            $criaOs($item, $clienteId, $statusId, $tipoStatus, $ts, $situacao, $valorPago);
            $totalOsGeradas++;
            $contagemStatus[$tipoStatus] = ($contagemStatus[$tipoStatus] ?? 0) + 1;
        }

        // ---- Despesas do mês (aluguel fixo, salários, fornecedor de peças) ----
        $despesasMes = [
            ['Aluguel do ponto comercial', precoEm(2200, 3200)],
            ['Salários da equipe', round($acumulado * (mt_rand(22, 30) / 100), 2)],
            ['Compra de peças — fornecedor', round($acumulado * (mt_rand(12, 18) / 100), 2)],
            ['Contas (água, luz, internet)', precoEm(600, 1100)],
        ];
        foreach ($despesasMes as [$descDesp, $valorDesp]) {
            if ($valorDesp <= 0) continue;
            $tsDesp = tsAleatorioNaJanela($mm['inicio'], $mm['fim']);
            $dataDesp = date('Y-m-d', $tsDesp);
            $stmtDespesa->execute([
                $eid, $contaId, $catDespesaId, $usuarioId, $descDesp, $valorDesp, $dataDesp, $dataDesp, 'transferencia',
            ]);
            $totalDespesas += $valorDesp;
        }

        echo "  OS geradas: {$osDoMes} (+ {$qtdPipeline} de pipeline) | Faturado: R$ " . number_format($acumulado, 2, ',', '.') . "\n";
    }

    $db->commit();

    echo str_repeat('-', 78) . "\n";
    echo "Concluído.\n";
    echo "OS geradas: {$totalOsGeradas} (numeração " . str_pad($inicio, $digitos, '0', STR_PAD_LEFT) . " a " . str_pad($numeroSeq - 1, $digitos, '0', STR_PAD_LEFT) . ")\n";
    echo "Clientes: " . count($clienteIds) . " | Produtos: " . count($produtoIdPorNome) . "\n";
    echo "Faturamento total lançado no Financeiro: R$ " . number_format($totalFaturado, 2, ',', '.') . "\n";
    echo "Despesas totais lançadas: R$ " . number_format($totalDespesas, 2, ',', '.') . "\n";
    echo "Distribuição por status:\n";
    foreach ($contagemStatus as $tipo => $n) echo "  - {$tipo}: {$n}\n";
    echo "\nPra apagar depois, ver o bloco de comentário no topo deste script (usar empresa_id={$eid}, último número " . str_pad($numeroSeq - 1, $digitos, '0', STR_PAD_LEFT) . ").\n";
} catch (Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, "Erro — nada foi gravado (rollback): " . $e->getMessage() . "\n");
    exit(1);
}
