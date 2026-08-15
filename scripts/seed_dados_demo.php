<?php
/**
 * Popula a empresa FixaOS (tenant já cadastrado no sistema) com clientes, produtos e ~1000
 * ordens de serviço FICTÍCIOS, espalhados nos últimos ~13 meses, pra servir de cenário em
 * vídeos institucionais/demonstrativos (dashboards, listas, financeiro etc. com dados
 * realistas em vez de uma conta vazia). Não usa nenhum dado de cliente real — nomes,
 * telefones, e-mails, defeitos e valores são todos gerados.
 *
 * Por padrão roda em modo SIMULAÇÃO (não grava nada, só mostra o que faria). Pra gravar:
 *   php scripts/seed_dados_demo.php --aplicar
 *
 * Opções:
 *   --empresa=ID   força o id da empresa (padrão: busca por nome_fantasia/razao_social LIKE '%FixaOS%')
 *   --qtd=N        quantidade de OS a gerar (padrão 1000)
 *   --inicio=N     número inicial da OS (padrão 145 — nunca gera abaixo do maior número já
 *                  existente na empresa, pra não colidir com a constraint única de numero)
 *
 * Exemplo:
 *   php scripts/seed_dados_demo.php --aplicar --qtd=1000 --inicio=145
 *
 * Pra apagar depois (ajuste {EID} e a faixa de número impressa no resumo final):
 *   DELETE FROM ordens_servico WHERE empresa_id={EID} AND numero BETWEEN '000145' AND '001144';
 *   -- equipamentos/os_servicos/os_pecas somem sozinhos via ON DELETE CASCADE da OS
 *   DELETE FROM clientes  WHERE empresa_id={EID} AND tags = 'seed-demo';
 *   DELETE FROM produtos  WHERE empresa_id={EID} AND codigo LIKE 'DEMO-%';
 *   DELETE FROM categorias_produto WHERE empresa_id={EID} AND nome LIKE 'DEMO: %';
 *   DELETE FROM servicos_catalogo  WHERE empresa_id={EID} AND descricao LIKE 'DEMO: %';
 */

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});
require BASE_PATH . '/app/Helpers/functions.php';

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

$qtd        = max(1, (int) $argOpt('qtd', 1000));
$inicioReq  = (int) $argOpt('inicio', 145);
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
         WHERE nome_fantasia LIKE '%FixaOS%' OR razao_social LIKE '%FixaOS%'"
    );
    $candidatos = $stmt->fetchAll();
    if (count($candidatos) === 0) {
        fwrite(STDERR, "Nenhuma empresa com nome contendo 'FixaOS' encontrada. Use --empresa=ID.\n");
        exit(1);
    }
    if (count($candidatos) > 1) {
        fwrite(STDERR, "Mais de uma empresa bateu com 'FixaOS' — escolha uma com --empresa=ID:\n");
        foreach ($candidatos as $c) fwrite(STDERR, "  #{$c['id']} — {$c['nome_fantasia']} ({$c['razao_social']})\n");
        exit(1);
    }
    $empresa = $candidatos[0];
}
$eid = (int) $empresa['id'];
echo "Empresa alvo: #{$eid} — {$empresa['nome_fantasia']} ({$empresa['razao_social']})\n";

// ---------------------------------------------------------------------------------------
// Contexto existente da empresa (status, categorias, dígitos do número de OS, técnicos)
// ---------------------------------------------------------------------------------------

$stmt = $db->prepare("SELECT id, tipo FROM os_status WHERE empresa_id = ?");
$stmt->execute([$eid]);
$statusPorTipo = [];
foreach ($stmt->fetchAll() as $r) $statusPorTipo[$r['tipo']][] = (int) $r['id'];
if (!$statusPorTipo) { fwrite(STDERR, "Empresa não tem os_status cadastrado — impossível gerar OS.\n"); exit(1); }

$stmt = $db->prepare("SELECT id, nome FROM categorias_equipamento WHERE empresa_id = ?");
$stmt->execute([$eid]);
$categoriaEquipId = [];
foreach ($stmt->fetchAll() as $r) $categoriaEquipId[$r['nome']] = (int) $r['id'];

$stmt = $db->prepare("SELECT id FROM usuarios WHERE empresa_id = ? AND ativo = 1 AND (perfil = 'tecnico' OR atende_os = 1)");
$stmt->execute([$eid]);
$tecnicos = array_column($stmt->fetchAll(), 'id');

$stmt = $db->prepare("SELECT chave, valor FROM configuracoes WHERE empresa_id = ? AND chave = 'os_digitos'");
$stmt->execute([$eid]);
$row = $stmt->fetch();
$digitos = $row ? (int) $row['valor'] : 6;

$stmt = $db->prepare("SELECT MAX(CAST(numero AS UNSIGNED)) FROM ordens_servico WHERE empresa_id = ?");
$stmt->execute([$eid]);
$maxExistente = (int) $stmt->fetchColumn();
$inicio = max($inicioReq, $maxExistente + 1);
if ($inicio !== $inicioReq) {
    echo "Aviso: já existe OS até o número {$maxExistente} nessa empresa — começando em {$inicio} em vez de {$inicioReq} pra não colidir.\n";
}

$temFechadaSemReceita = (bool) $db->query("SHOW COLUMNS FROM ordens_servico LIKE 'fechada_sem_receita'")->fetch();

echo "Técnicos disponíveis pra atribuir: " . count($tecnicos) . "\n";
echo "OS a gerar: {$qtd}, numeração de {$inicio} a " . ($inicio + $qtd - 1) . " (zero-padded a {$digitos} dígitos)\n";
echo str_repeat('-', 78) . "\n";

// ---------------------------------------------------------------------------------------
// Pools de dados fictícios
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

$CIDADES_EXTRA = [['Campinas','SP'],['Sorocaba','SP'],['Jundiaí','SP'],['Osasco','SP'],['Guarulhos','SP'],
    ['São Bernardo do Campo','SP'],['Santo André','SP'],['Barueri','SP']];

// [empresa_id, tags='seed-demo'] marca todo cliente gerado — facilita apagar depois.
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
    $r = mt_rand(1, $total);
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

// Categorias de equipamento — precisam bater com os nomes já semeados em LandingController.
$CATEGORIAS = [
    'Celular/Smartphone' => [
        'peso' => 28, 'voltagem' => ['bateria'],
        'aparelhos' => [['Samsung','Galaxy A54'],['Samsung','Galaxy S23'],['Samsung','Galaxy M34'],
            ['Motorola','Moto G84'],['Motorola','Edge 40'],['Apple','iPhone 11'],['Apple','iPhone 12'],
            ['Apple','iPhone 13'],['Xiaomi','Redmi Note 12'],['Xiaomi','Poco X5']],
        'defeitos' => ['Tela quebrada após queda','Não liga mais','Bateria não segura carga','Molhou e parou de funcionar',
            'Câmera traseira não focaliza','Alto-falante sem som','Botão de volume não responde',
            'Tela com manchas roxas (queima)','Não carrega, conector solto','Reiniciando sozinho'],
        'servicos' => [['Troca de tela',280,650],['Troca de bateria',90,180],['Troca de conector de carga',80,150],
            ['Reparo de placa (curto de energia)',150,400],['Diagnóstico técnico',40,60]],
        'pecas' => [['Tela Samsung Galaxy A54',320,0.68],['Tela iPhone 12',420,0.70],['Bateria Motorola Moto G84',95,0.62],
            ['Flat de carga USB-C',45,0.55],['Alto-falante genérico',35,0.50]],
        'acessorios' => ['Carregador','Capinha','Sem acessórios','Carregador e fone','Capinha e película'],
    ],
    'Tablet' => [
        'peso' => 6, 'voltagem' => ['bateria'],
        'aparelhos' => [['Samsung','Galaxy Tab A8'],['Apple','iPad 9ª geração'],['Multilaser','M10'],['Lenovo','Tab M10']],
        'defeitos' => ['Tela não responde ao toque','Não carrega','Tela trincada','Travando com frequência','Não liga'],
        'servicos' => [['Troca de tela',220,480],['Troca de bateria',110,220],['Formatação com backup',80,140]],
        'pecas' => [['Tela Tablet 10"',260,0.65],['Bateria Tablet',120,0.60]],
        'acessorios' => ['Carregador','Capa','Sem acessórios'],
    ],
    'Notebook' => [
        'peso' => 14, 'voltagem' => ['bivolt'],
        'aparelhos' => [['Dell','Inspiron 15'],['Lenovo','Ideapad 3'],['Acer','Aspire 5'],['Samsung','Book X30'],
            ['HP','Pavilion 14'],['Positivo','Vaio FE14']],
        'defeitos' => ['Muito lento, travando','Não liga','Tela quebrada','Superaquecendo e desligando sozinho',
            'Teclado com teclas não funcionando','HD com barulho estranho','Não reconhece a bateria','Tela azul frequente'],
        'servicos' => [['Formatação com backup',120,220],['Limpeza interna (troca de pasta térmica)',90,150],
            ['Troca de tela',350,700],['Upgrade de memória RAM',80,180],['Troca de HD para SSD',150,350],
            ['Troca de teclado',120,260]],
        'pecas' => [['SSD 240GB',180,0.65],['Memória RAM 8GB DDR4',150,0.68],['Tela Notebook 15.6"',380,0.66],
            ['Teclado Notebook',140,0.55],['Cooler/ventoinha',70,0.50]],
        'acessorios' => ['Carregador','Mochila','Sem acessórios','Carregador e mouse'],
    ],
    'Desktop/PC' => [
        'peso' => 6, 'voltagem' => ['bivolt'],
        'aparelhos' => [['Positivo','Master'],['Dell','OptiPlex'],['Montagem própria','Gamer'],['Lenovo','ThinkCentre']],
        'defeitos' => ['Não liga','Reiniciando sozinho','Muito lento','Sem imagem no monitor','Fazendo barulho alto no cooler'],
        'servicos' => [['Formatação com backup',100,200],['Limpeza interna completa',70,130],['Troca de fonte',90,180],
            ['Upgrade de memória RAM',80,180],['Montagem/troca de placa de vídeo',150,400]],
        'pecas' => [['Fonte ATX 500W',180,0.62],['Memória RAM 8GB DDR4',150,0.68],['SSD 240GB',180,0.65],
            ['Cooler gabinete',45,0.50]],
        'acessorios' => ['Sem acessórios','Gabinete e cabo de força'],
    ],
    'Televisão' => [
        'peso' => 13, 'voltagem' => ['bivolt','110v','220v'],
        'aparelhos' => [['Samsung','Smart TV 50" Crystal UHD'],['LG','Smart TV 43" UHD'],['AOC','Smart TV 32"'],
            ['Philco','Smart TV 55"'],['Multilaser','TL45'],['Samsung','TV 32" HD']],
        'defeitos' => ['Não liga, luz do standby não acende','Tela com listras coloridas','Imagem com metade da tela apagada',
            'Sem som','Reiniciando sozinha','Tela quebrada','Não sintoniza os canais'],
        'servicos' => [['Troca de fonte',150,320],['Troca de placa principal',250,550],['Troca da tela',600,1400],
            ['Reparo de backlight (LEDs)',180,380],['Diagnóstico técnico',50,80]],
        'pecas' => [['Fonte TV LED 50"',180,0.60],['Placa principal TV (genérica)',320,0.65],['Kit LED backlight',150,0.55],
            ['Placa T-Con',140,0.55]],
        'acessorios' => ['Controle remoto','Sem acessórios','Controle e cabo de força'],
    ],
    'Monitor' => [
        'peso' => 5, 'voltagem' => ['bivolt'],
        'aparelhos' => [['LG','Monitor 21.5" IPS'],['Samsung','Monitor 24" Curvo'],['AOC','Monitor 21.5"'],['Dell','Monitor 24"']],
        'defeitos' => ['Sem imagem','Tela com linhas verticais','Não liga','Piscando constantemente','Cores desbotadas'],
        'servicos' => [['Troca de fonte interna',90,160],['Troca de placa principal',150,280],['Diagnóstico técnico',40,60]],
        'pecas' => [['Fonte Monitor LED',80,0.55],['Placa principal Monitor',160,0.60]],
        'acessorios' => ['Cabo de vídeo','Sem acessórios'],
    ],
    'Geladeira/Freezer' => [
        'peso' => 9, 'voltagem' => ['110v','220v'],
        'aparelhos' => [['Brastemp','Frost Free Duplex'],['Consul','Frost Free 340L'],['Electrolux','DF44'],
            ['Panasonic','Frost Free'],['Continental','Duplex']],
        'defeitos' => ['Não está gelando','Fazendo gelo em excesso no congelador','Barulho alto no compressor',
            'Vazando água por baixo','Motor não liga','Borracha da porta ressecada, não veda'],
        'servicos' => [['Troca de compressor',350,650],['Troca de termostato',90,180],['Reparo no sistema de degelo',180,380],
            ['Troca de borracha de vedação',80,150],['Visita técnica com diagnóstico',60,90]],
        'pecas' => [['Compressor 1/4HP',420,0.70],['Termostato Geladeira',95,0.55],['Resistência de degelo',110,0.55],
            ['Borracha de vedação porta',85,0.50]],
        'acessorios' => ['Sem acessórios'],
    ],
    'Máquina de Lavar' => [
        'peso' => 8, 'voltagem' => ['110v','220v'],
        'aparelhos' => [['Brastemp','BWK11'],['Consul','CWH12'],['Electrolux','LAC12'],['LG','WD-1408']],
        'defeitos' => ['Não centrifuga','Vazando água','Não liga','Fazendo muito barulho na centrifugação',
            'Não drena a água','Tampa não trava','Parando no meio do ciclo'],
        'servicos' => [['Troca de resistência',120,220],['Troca de rolamento do tambor',250,480],['Troca de placa eletrônica',280,550],
            ['Desentupimento do dreno',80,140],['Troca de correia',90,160]],
        'pecas' => [['Resistência Máquina de Lavar',130,0.58],['Kit rolamento tambor',180,0.60],['Placa eletrônica (genérica)',320,0.65],
            ['Correia de transmissão',60,0.50]],
        'acessorios' => ['Mangueira de saída','Sem acessórios'],
    ],
    'Micro-ondas' => [
        'peso' => 3, 'voltagem' => ['110v','220v'],
        'aparelhos' => [['Panasonic','NN-ST25'],['Electrolux','MTD30'],['Consul','CMS26'],['LG','MS3595']],
        'defeitos' => ['Não esquenta','Prato giratório não gira','Faíscas dentro do forno','Não liga','Painel não responde ao toque'],
        'servicos' => [['Troca de magnetron',180,320],['Troca de fusível/capacitor',60,110],['Troca de motor do prato',80,150]],
        'pecas' => [['Magnetron',210,0.62],['Fusível de alta tensão',30,0.45],['Motor prato giratório',65,0.50]],
        'acessorios' => ['Prato giratório','Sem acessórios'],
    ],
    'Ar Condicionado' => [
        'peso' => 4, 'voltagem' => ['220v'],
        'aparelhos' => [['Springer Midea','Split 9000 BTUs'],['Samsung','Split 12000 BTUs'],['LG','Split Dual Inverter'],
            ['Elgin','Split 9000 BTUs']],
        'defeitos' => ['Não está gelando','Vazando água na parede interna','Fazendo barulho excessivo','Não liga',
            'Gás baixo (perdendo eficiência)','Filtro sujo, mau cheiro'],
        'servicos' => [['Recarga de gás',180,320],['Higienização completa',120,200],['Troca de placa eletrônica',280,520],
            ['Instalação de suporte novo',150,280]],
        'pecas' => [['Placa eletrônica evaporadora',290,0.60],['Gás refrigerante R410 (carga)',150,0.55]],
        'acessorios' => ['Controle remoto','Sem acessórios'],
    ],
    'Impressora' => [
        'peso' => 3, 'voltagem' => ['bivolt'],
        'aparelhos' => [['HP','DeskJet Ink Advantage'],['Epson','EcoTank L3250'],['Canon','Pixma'],['Brother','DCP-T420W']],
        'defeitos' => ['Não puxa o papel','Manchando as impressões','Cabeça de impressão entupida','Não reconhece cartucho',
            'Não liga','Wifi não conecta'],
        'servicos' => [['Limpeza de cabeça de impressão',60,110],['Troca de cartucho/tanque',50,150],
            ['Reparo no mecanismo de tração de papel',80,160]],
        'pecas' => [['Cartucho de tinta (genérico)',70,0.45],['Cabeça de impressão',180,0.55]],
        'acessorios' => ['Cabo USB','Sem acessórios'],
    ],
    'Videogame' => [
        'peso' => 1, 'voltagem' => ['bivolt'],
        'aparelhos' => [['Sony','PlayStation 4 Slim'],['Sony','PlayStation 5'],['Microsoft','Xbox Series S'],['Nintendo','Switch']],
        'defeitos' => ['Superaquecendo e desligando','Não lê os discos','Controle não conecta','Não liga','Barulho alto na ventoinha'],
        'servicos' => [['Troca de pasta térmica e limpeza',90,150],['Troca de leitor de disco',150,280],['Reparo de HDMI',120,220]],
        'pecas' => [['Leitor de disco (genérico)',160,0.55],['Fonte de videogame',110,0.55]],
        'acessorios' => ['Controle','Cabo HDMI','Sem acessórios'],
    ],
];

$CORES = ['Preto','Branco','Prata','Cinza espacial','Azul','Vermelho','Dourado','Inox'];

// ---------------------------------------------------------------------------------------
// Linha do tempo: $qtd datas ascendentes cobrindo ~13 meses até agora, em horário comercial
// ---------------------------------------------------------------------------------------

$agora = time();
$offsets = [];
for ($i = 0; $i < $qtd; $i++) $offsets[] = mt_rand(1, 400) + mt_rand(0, 99) / 100;
rsort($offsets); // maior offset (mais antigo) primeiro -> numero cresce com o tempo

$datas = [];
foreach ($offsets as $off) {
    $ts = $agora - (int) round($off * 86400);
    $hora = mt_rand(8, 18);
    $min  = mt_rand(0, 59);
    $ts = mktime($hora, $min, 0, (int) date('n', $ts), (int) date('j', $ts), (int) date('Y', $ts));
    if ((int) date('N', $ts) === 7) $ts += 86400; // domingo -> segunda
    $datas[] = $ts;
}

// ---------------------------------------------------------------------------------------
// Pool de clientes fictícios (gerado em memória primeiro pra poder calcular criado_em
// coerente com a data da primeira OS de cada um)
// ---------------------------------------------------------------------------------------

$totalClientes = min(280, max(60, (int) round($qtd * 0.28)));
$clientesPool = [];
for ($i = 0; $i < $totalClientes; $i++) {
    $nome = nomeAleatorio($NOMES_M, $NOMES_F, $SOBRENOMES);
    $ehLocal = mt_rand(1, 100) <= 85;
    // Cliente local: deixa cidade/uf em branco (mesma cidade da empresa, não precisa repetir).
    [$cidade, $uf] = $ehLocal ? [null, null] : $CIDADES_EXTRA[array_rand($CIDADES_EXTRA)];
    $ddd = '11';
    $tel = '9' . mt_rand(6000, 9999) . '-' . mt_rand(1000, 9999);
    $slug = strtolower(str_replace(' ', '.', preg_replace('/[^a-zA-Z ]/', '', $nome)));
    $clientesPool[] = [
        'nome' => $nome,
        'telefone' => "({$ddd}) {$tel}",
        'email' => $slug . mt_rand(1, 99) . '@' . ['gmail.com','hotmail.com','outlook.com','yahoo.com.br'][array_rand(['gmail.com','hotmail.com','outlook.com','yahoo.com.br'])],
        'cpf' => sprintf('%03d.%03d.%03d-%02d', mt_rand(0,999), mt_rand(0,999), mt_rand(0,999), mt_rand(0,99)),
        'cidade' => $ehLocal ? null : $cidade,
        'uf' => $ehLocal ? null : $uf,
        'origem' => weightedPick(['balcao'=>35,'whatsapp'=>30,'indicacao'=>20,'telefone'=>10,'site'=>5]),
        'menor_data' => null,
    ];
}

// distribui as $qtd OS entre os clientes com peso (simula clientes recorrentes)
$pesosCliente = [];
for ($i = 0; $i < $totalClientes; $i++) $pesosCliente[$i] = mt_rand(1, 5) ** 2;
$poolPesado = [];
foreach ($pesosCliente as $idx => $peso) for ($p = 0; $p < $peso; $p++) $poolPesado[] = $idx;

$osClienteIdx = [];
for ($i = 0; $i < $qtd; $i++) {
    $idx = $poolPesado[array_rand($poolPesado)];
    $osClienteIdx[$i] = $idx;
    if ($clientesPool[$idx]['menor_data'] === null || $datas[$i] < $clientesPool[$idx]['menor_data']) {
        $clientesPool[$idx]['menor_data'] = $datas[$i];
    }
}

echo "Clientes fictícios a gerar: {$totalClientes}\n";
echo str_repeat('-', 78) . "\n";

// ---------------------------------------------------------------------------------------
// Grava (ou simula) tudo
// ---------------------------------------------------------------------------------------

$clienteIds = [];
$produtoIdPorNome = [];
$totalValor = 0.0;
$contagemStatus = [];

if (!$aplicar) {
    echo "Prévia (nada gravado) — exemplo dos 3 primeiros clientes:\n";
    for ($i = 0; $i < min(3, $totalClientes); $i++) {
        $c = $clientesPool[$i];
        echo "  - {$c['nome']} | {$c['telefone']} | {$c['email']} | cliente desde " . date('d/m/Y', $c['menor_data'] ?? $agora) . "\n";
    }
    echo "\nPrévia das 3 primeiras OS (numeração {$inicio}..):\n";
    for ($i = 0; $i < min(3, $qtd); $i++) {
        $cat = weightedPick(array_combine(array_keys($CATEGORIAS), array_column($CATEGORIAS, 'peso')));
        $ap  = $CATEGORIAS[$cat]['aparelhos'][array_rand($CATEGORIAS[$cat]['aparelhos'])];
        echo "  - OS " . str_pad($inicio + $i, $digitos, '0', STR_PAD_LEFT) . " | " . date('d/m/Y H:i', $datas[$i])
            . " | {$clientesPool[$osClienteIdx[$i]]['nome']} | {$cat} {$ap[0]} {$ap[1]}\n";
    }
    echo "\nRode com --aplicar pra gravar de verdade ({$qtd} OS, {$totalClientes} clientes, produtos e catálogo de serviços).\n";
    exit;
}

$db->beginTransaction();

try {
    // Clientes
    $stmtCli = $db->prepare(
        "INSERT INTO clientes (empresa_id, tipo, nome, cpf_cnpj, telefone, whatsapp, email, cidade, uf, origem, status, tags, criado_em)
         VALUES (?, 'pf', ?, ?, ?, ?, ?, ?, ?, ?, 'ativo', 'seed-demo', ?)"
    );
    foreach ($clientesPool as $c) {
        $criadoEm = date('Y-m-d H:i:s', ($c['menor_data'] ?? $agora) - mt_rand(0, 15) * 86400);
        $stmtCli->execute([
            $eid, $c['nome'], $c['cpf'], $c['telefone'], $c['telefone'], $c['email'],
            $c['cidade'], $c['uf'], $c['origem'], $criadoEm,
        ]);
        $clienteIds[] = (int) $db->lastInsertId();
    }
    echo "Clientes gravados: " . count($clienteIds) . "\n";

    // Categorias de produto + produtos (peças) — reaproveita categoria existente com mesmo nome se houver
    $stmt = $db->prepare("SELECT id, nome FROM categorias_produto WHERE empresa_id = ?");
    $stmt->execute([$eid]);
    $catProdId = [];
    foreach ($stmt->fetchAll() as $r) $catProdId[$r['nome']] = (int) $r['id'];

    $stmtCatProd = $db->prepare("INSERT INTO categorias_produto (empresa_id, nome) VALUES (?, ?)");
    $stmtProd = $db->prepare(
        "INSERT INTO produtos (empresa_id, categoria_id, codigo, nome, unidade, estoque_atual, estoque_minimo, valor_custo, valor_venda, ativo)
         VALUES (?, ?, ?, ?, 'un', ?, ?, ?, ?, 1)"
    );
    $codigoSeq = 1;
    foreach ($CATEGORIAS as $catNome => $catDados) {
        $nomeCatProd = "DEMO: {$catNome}";
        if (!isset($catProdId[$nomeCatProd])) {
            $stmtCatProd->execute([$eid, $nomeCatProd]);
            $catProdId[$nomeCatProd] = (int) $db->lastInsertId();
        }
        foreach ($catDados['pecas'] as [$nomePeca, $venda, $custoPct]) {
            if (isset($produtoIdPorNome[$nomePeca])) continue; // mesma peça usada em outra categoria (ex.: SSD em Notebook e Desktop)
            $codigo = 'DEMO-' . str_pad((string) $codigoSeq++, 4, '0', STR_PAD_LEFT);
            $custo  = round($venda * $custoPct, 2);
            $stmtProd->execute([
                $eid, $catProdId[$nomeCatProd], $codigo, $nomePeca,
                mt_rand(2, 40), mt_rand(2, 5), $custo, $venda,
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
    $servicoValorPadrao = [];
    foreach ($CATEGORIAS as $catDados) {
        foreach ($catDados['servicos'] as [$desc, $min, $max]) {
            $nomeServ = "DEMO: {$desc}";
            $valorPadrao = precoEm($min, $max);
            $servicoValorPadrao[$desc] = [$min, $max];
            if (!in_array($nomeServ, $servicosExistentes, true)) {
                $stmtServCat->execute([$eid, $nomeServ, $valorPadrao]);
                $servicosExistentes[] = $nomeServ;
            }
        }
    }
    echo "Serviços no catálogo: " . count($servicoValorPadrao) . "\n";
    echo str_repeat('-', 78) . "\n";
    echo "Gerando {$qtd} ordens de serviço...\n";

    // Ordens de serviço
    $stmtEquip = $db->prepare(
        "INSERT INTO equipamentos (empresa_id, cliente_id, categoria_id, tipo, marca, modelo, numero_serie, imei, cor, voltagem, estado_entrada, descricao_defeito_cliente, acessorios)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
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
    $sqlFecha = $temFechadaSemReceita
        ? "UPDATE ordens_servico SET fechada_sem_receita = ? WHERE id = ?"
        : null;
    $stmtFecha = $sqlFecha ? $db->prepare($sqlFecha) : null;

    $catKeys = array_keys($CATEGORIAS);
    $catPesos = array_combine($catKeys, array_column($CATEGORIAS, 'peso'));

    for ($i = 0; $i < $qtd; $i++) {
        $dataEntrada = $datas[$i];
        $diasAtras = ($agora - $dataEntrada) / 86400;

        $tipoStatus = $diasAtras < 3
            ? weightedPick(array_intersect_key(['aberta'=>35,'em_andamento'=>35,'aguardando'=>25,'concluida'=>5], $statusPorTipo))
            : ($diasAtras < 10
                ? weightedPick(array_intersect_key(['em_andamento'=>20,'aguardando'=>20,'concluida'=>25,'entregue'=>25,'cancelada'=>10], $statusPorTipo))
                : weightedPick(array_intersect_key(['entregue'=>76,'cancelada'=>13,'concluida'=>9,'aguardando'=>2], $statusPorTipo)));
        if (!isset($statusPorTipo[$tipoStatus])) $tipoStatus = array_key_first($statusPorTipo);
        $statusId = $statusPorTipo[$tipoStatus][array_rand($statusPorTipo[$tipoStatus])];

        $catNome = weightedPick($catPesos);
        $cat = $CATEGORIAS[$catNome];
        [$marca, $modelo] = $cat['aparelhos'][array_rand($cat['aparelhos'])];
        $defeito = $cat['defeitos'][array_rand($cat['defeitos'])];
        $acessorio = $cat['acessorios'][array_rand($cat['acessorios'])];
        $voltagem = $cat['voltagem'][array_rand($cat['voltagem'])];
        $cor = $CORES[array_rand($CORES)];
        $ehCelularTablet = in_array($catNome, ['Celular/Smartphone','Tablet'], true);

        $clienteId = $clienteIds[$osClienteIdx[$i]];

        $numSerie = strtoupper(substr(md5(uniqid((string) $i, true)), 0, 10));
        $imei = $ehCelularTablet ? (string) mt_rand(100000000000000, 999999999999999) : null;

        $stmtEquip->execute([
            $eid, $clienteId, $categoriaEquipId[$catNome] ?? null, $catNome, $marca, $modelo,
            $numSerie, $imei, $cor, $voltagem, weightedPick(['bom'=>50,'regular'=>35,'ruim'=>10,'otimo'=>5]),
            $defeito, $acessorio,
        ]);
        $equipId = (int) $db->lastInsertId();

        $tipoServico = $tipoStatus === 'cancelada'
            ? weightedPick(['orcamento'=>70,'conserto'=>30])
            : weightedPick(['conserto'=>78,'orcamento'=>10,'garantia'=>6,'manutencao'=>4,'instalacao'=>2]);

        // Serviços (1-2 itens)
        $qtdServ = mt_rand(1, count($cat['servicos']) > 1 ? 2 : 1);
        $servEscolhidos = (array) array_rand($cat['servicos'], min($qtdServ, count($cat['servicos'])));
        $subtotalServ = 0.0;
        $itensServ = [];
        foreach ($servEscolhidos as $si) {
            [$desc, $min, $max] = $cat['servicos'][$si];
            $valor = precoEm($min, $max);
            $itensServ[] = [$desc, $valor];
            $subtotalServ += $valor;
        }

        // Peças (0-2 itens, mais provável em conserto do que em orçamento/garantia)
        $chancePeca = $tipoServico === 'conserto' ? 70 : 25;
        $itensPeca = [];
        $subtotalPeca = 0.0;
        if (mt_rand(1, 100) <= $chancePeca && $cat['pecas']) {
            $qtdPeca = mt_rand(1, count($cat['pecas']) > 1 ? 2 : 1);
            $pecaEscolhidas = (array) array_rand($cat['pecas'], min($qtdPeca, count($cat['pecas'])));
            foreach ($pecaEscolhidas as $pi) {
                [$nomePeca] = $cat['pecas'][$pi];
                if (!isset($produtoIdPorNome[$nomePeca])) continue;
                $p = $produtoIdPorNome[$nomePeca];
                $itensPeca[] = [$nomePeca, $p['id'], $p['custo'], $p['venda']];
                $subtotalPeca += $p['venda'];
            }
        }

        $subtotal = $subtotalServ + $subtotalPeca;
        $temDesconto = mt_rand(1, 100) <= 12;
        $descontoPct = $temDesconto ? mt_rand(5, 15) : 0;
        $descontoValor = $temDesconto ? round($subtotal * $descontoPct / 100, 2) : 0.0;

        $fechadaSemReceita = 0;
        $valorDiagnostico = 0.0;

        if ($tipoStatus === 'cancelada') {
            // Orçamento recusado: metade sem cobrança nenhuma, metade com taxa de diagnóstico cobrada.
            if (mt_rand(1, 100) <= 50) {
                $valorTotal = 0.0;
                $fechadaSemReceita = 1;
                $itensServ = [];
                $itensPeca = [];
            } else {
                $valorDiagnostico = precoEm(40, 70);
                $valorTotal = $valorDiagnostico;
                $itensServ = [['Diagnóstico técnico', $valorDiagnostico]];
                $itensPeca = [];
            }
        } else {
            $valorTotal = max(0, round($subtotal - $descontoValor, 2));
        }

        switch ($tipoStatus) {
            case 'entregue':
                $situacao = mt_rand(1, 100) <= 92 ? 'pago' : 'parcial';
                $valorPago = $situacao === 'pago' ? $valorTotal : round($valorTotal * mt_rand(40, 80) / 100, 2);
                break;
            case 'concluida':
                $situacao = mt_rand(1, 100) <= 30 ? 'pago' : ($valorTotal > 0 && mt_rand(1, 100) <= 20 ? 'parcial' : 'pendente');
                $valorPago = $situacao === 'pago' ? $valorTotal : ($situacao === 'parcial' ? round($valorTotal * 0.5, 2) : 0.0);
                break;
            case 'cancelada':
                $situacao = $valorTotal > 0 ? 'pago' : 'pendente';
                $valorPago = $valorTotal;
                break;
            default:
                $situacao = 'pendente';
                $valorPago = 0.0;
        }

        $prazoDias = mt_rand(2, 7);
        $dataPrevisao = $dataEntrada + $prazoDias * 86400;
        $dataConclusao = null;
        $dataEntrega = null;
        $garantiaAte = null;
        $defeitoConstatado = null;
        $laudoTecnico = null;
        $solucaoAplicada = null;

        if (in_array($tipoStatus, ['concluida', 'entregue', 'cancelada'], true)) {
            $dataConclusao = $dataEntrada + mt_rand(1, max(1, $prazoDias)) * 86400;
            $defeitoConstatado = 'Confirmado: ' . lcfirst($defeito);
            if ($itensServ) $solucaoAplicada = implode('; ', array_column($itensServ, 0));
            if (mt_rand(1, 100) <= 20) $laudoTecnico = '<p>Equipamento testado após o serviço, funcionando normalmente. Sem reincidência do defeito relatado.</p>';
        }
        if ($tipoStatus === 'entregue') {
            $dataEntrega = $dataConclusao + mt_rand(0, 4) * 86400;
            $garantiaAte = date('Y-m-d', $dataEntrega + 90 * 86400);
        }

        $prioridade = weightedPick(['normal'=>70,'alta'=>15,'baixa'=>10,'urgente'=>5]);
        $tecnicoId = $tecnicos ? $tecnicos[array_rand($tecnicos)] : null;
        $numero = str_pad($inicio + $i, $digitos, '0', STR_PAD_LEFT);

        $stmtOs->execute([
            $eid, $numero, $clienteId, $equipId, $statusId, $tecnicoId, $prioridade, $tipoServico,
            $defeito, $defeitoConstatado, $laudoTecnico, $solucaoAplicada,
            $valorDiagnostico, $descontoPct, $descontoValor, $valorTotal, $valorPago, $situacao,
            90, $garantiaAte, date('Y-m-d H:i:s', $dataEntrada), date('Y-m-d H:i:s', $dataPrevisao),
            $dataConclusao ? date('Y-m-d H:i:s', $dataConclusao) : null,
            $dataEntrega ? date('Y-m-d H:i:s', $dataEntrega) : null,
        ]);
        $osId = (int) $db->lastInsertId();

        if ($stmtFecha && $fechadaSemReceita) $stmtFecha->execute([1, $osId]);

        foreach ($itensServ as [$desc, $valor]) {
            $stmtServ->execute([$eid, $osId, $desc, $valor, $valor, $tecnicoId, in_array($tipoStatus, ['concluida','entregue'], true) ? 1 : 0]);
        }
        foreach ($itensPeca as [$nomePeca, $produtoId, $custo, $venda]) {
            $stmtPeca->execute([$eid, $osId, $produtoId, $nomePeca, $custo, $venda, $venda]);
        }

        $totalValor += $valorTotal;
        $contagemStatus[$tipoStatus] = ($contagemStatus[$tipoStatus] ?? 0) + 1;

        if (($i + 1) % 100 === 0) {
            $db->commit();
            $db->beginTransaction();
            echo "  ... " . ($i + 1) . "/{$qtd} OS gravadas\n";
        }
    }

    $db->commit();

    echo str_repeat('-', 78) . "\n";
    echo "Concluído.\n";
    echo "OS geradas: {$qtd} (numeração " . str_pad($inicio, $digitos, '0', STR_PAD_LEFT) . " a " . str_pad($inicio + $qtd - 1, $digitos, '0', STR_PAD_LEFT) . ")\n";
    echo "Clientes: " . count($clienteIds) . " | Produtos: " . count($produtoIdPorNome) . "\n";
    echo "Valor total somado (valor_total das OS): R$ " . number_format($totalValor, 2, ',', '.') . "\n";
    echo "Distribuição por status:\n";
    foreach ($contagemStatus as $tipo => $n) echo "  - {$tipo}: {$n}\n";
    echo "\nPra apagar depois, ver o bloco de comentário no topo deste script (usar empresa_id={$eid}).\n";
} catch (Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, "Erro — nada foi gravado (rollback): " . $e->getMessage() . "\n");
    exit(1);
}
