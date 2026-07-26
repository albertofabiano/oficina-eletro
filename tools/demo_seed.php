<?php
/**
 * Seed + reset da EMPRESA DEMONSTRAÇÃO do FixaOS.
 * Cria (idempotente) a empresa/usuário demo e popula com ~2 anos de histórico
 * realista (OS, financeiro) com faturamento mensal expressivo (R$30-50 mil/mês),
 * pra deixar os gráficos do dashboard/financeiro atraentes pra quem testa a demo.
 * Roda no setup e no reset automático (cron). Uso: php demo_seed.php
 */
$pdo = new PDO('mysql:host=localhost;dbname=fixaos;charset=utf8mb4', 'fixaos', 'Fixa@2024',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$DEMO_EMAIL = 'demo@fixaos.com.br';

// ── 1. Empresa + usuário demo (find-or-create) ───────────────────────────────
$u = $pdo->prepare("SELECT id, empresa_id FROM usuarios WHERE email = ? LIMIT 1");
$u->execute([$DEMO_EMAIL]);
$row = $u->fetch(PDO::FETCH_ASSOC);

if ($row) {
    $eid = (int) $row['empresa_id'];
    $uid = (int) $row['id'];
} else {
    $pdo->prepare("INSERT INTO empresas (razao_social, nome_fantasia, cidade, uf, ativo, listagem_publica, tipo_conta, plano, trial_ate)
                   VALUES ('FixaOS Demonstracao','Assistencia Modelo (Demo)','Sao Paulo','SP',1,0,'completo','profissional', DATE_ADD(CURDATE(), INTERVAL 3650 DAY))")
        ->execute();
    $eid = (int) $pdo->lastInsertId();
    $senha = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT, ['cost' => 12]);
    $pdo->prepare("INSERT INTO usuarios (empresa_id, nome, email, senha, perfil, ativo)
                   VALUES (?, 'Voce (Demonstracao)', ?, ?, 'admin', 1)")
        ->execute([$eid, $DEMO_EMAIL, $senha]);
    $uid = (int) $pdo->lastInsertId();
}

// ── 1b. Restaura a config da empresa/usuário demo (caso alguém altere) ───────
$pdo->prepare("UPDATE empresas SET nome_fantasia='Assistencia Modelo (Demo)', razao_social='FixaOS Demonstracao', listagem_publica=0, ativo=1, financeiro_inicio=NULL WHERE id=?")->execute([$eid]);
$pdo->prepare("UPDATE usuarios SET nome='Voce (Demonstracao)', perfil='admin', ativo=1 WHERE id=?")->execute([$uid]);

// ── 2. Reset: limpa os dados da empresa demo (filhos antes) ───────────────────
foreach (['fin_lancamentos','os_pecas','os_servicos','os_historico','ordens_servico',
          'equipamentos','produtos','clientes','os_status','categorias_equipamento',
          'fin_categorias','fin_contas','equip_marcas','equip_tipos','equip_acessorios',
          'produto_marcas','produto_tipos','produto_estados','categorias_produto','movimentos_estoque',
          'agenda','notificacoes'] as $t) {
    $pdo->prepare("DELETE FROM `$t` WHERE empresa_id = ?")->execute([$eid]);
}

$ins = function (string $sql, array $p) use ($pdo): int {
    $pdo->prepare($sql)->execute($p);
    return (int) $pdo->lastInsertId();
};

// ── 3. Config: status de OS (workflow) ───────────────────────────────────────
$statusDefs = [
    ['Aguardando Diagnostico', '#6c757d', 1, 'aberta',       0],
    ['Em Diagnostico',         '#0dcaf0', 2, 'em_andamento', 0],
    ['Aguardando Aprovacao',   '#ffc107', 3, 'aguardando',   0],
    ['Aprovado',               '#0d6efd', 4, 'em_andamento', 0],
    ['Em Reparo',              '#6610f2', 5, 'em_andamento', 0],
    ['Pronto',                 '#198754', 6, 'concluida',    1],
    ['Entregue',               '#20c997', 7, 'entregue',     0],
    ['Cancelado',              '#dc3545', 8, 'cancelada',    0],
];
$status = [];
foreach ($statusDefs as $s) {
    $status[$s[0]] = $ins(
        "INSERT INTO os_status (empresa_id, nome, cor, ordem, tipo, cor_fonte, permite_fechar) VALUES (?,?,?,?,?, '#ffffff', ?)",
        [$eid, $s[0], $s[1], $s[2], $s[3], $s[4]]
    );
}

// ── Config: conta, categorias financeiras, categorias de equipamento ─────────
$conta = $ins("INSERT INTO fin_contas (empresa_id, nome, tipo, saldo_inicial, saldo_atual, ativo) VALUES (?, 'Caixa', 'caixa', 0, 0, 1)", [$eid]);
$catRec = [];
foreach (['Servicos','Venda de pecas'] as $c) $catRec[$c] = $ins("INSERT INTO fin_categorias (empresa_id, tipo, nome, status, cor) VALUES (?, 'receita', ?, 'ativo', '#198754')", [$eid, $c]);
$catDes = [];
foreach ([['Compra de pecas','#dc3545'],['Aluguel','#f97316'],['Contas (luz/net)','#6c757d']] as $c) $catDes[$c[0]] = $ins("INSERT INTO fin_categorias (empresa_id, tipo, nome, status, cor) VALUES (?, 'despesa', ?, 'ativo', ?)", [$eid, $c[0], $c[1]]);
foreach (['Celular','TV','Notebook','Eletrodomestico','Outros'] as $c) $ins("INSERT INTO categorias_equipamento (empresa_id, nome) VALUES (?, ?)", [$eid, $c]);
foreach (['Samsung','Apple','Motorola','LG','Xiaomi','Dell','Positivo','Philco'] as $m) $ins("INSERT INTO equip_marcas (empresa_id, nome) VALUES (?, ?)", [$eid, $m]);
foreach (['Celular','TV','Notebook','Tablet','Micro-ondas','Som'] as $tp) $ins("INSERT INTO equip_tipos (empresa_id, nome) VALUES (?, ?)", [$eid, $tp]);
foreach (['Sem acessorios','Carregador','Capa','Cabo','Controle remoto'] as $ac) $ins("INSERT INTO equip_acessorios (empresa_id, nome) VALUES (?, ?)", [$eid, $ac]);

// ── Produtos (pecas / placas) ────────────────────────────────────────────────
$produtos = [
    ['Tela Samsung Galaxy A20', 120.00, 280.00, 8],
    ['Bateria iPhone 11', 90.00, 220.00, 5],
    ['Placa de carga Motorola G8', 45.00, 130.00, 12],
    ['Conector USB-C (flex de carga)', 15.00, 60.00, 30],
    ['Fonte / placa TV LG 43"', 160.00, 350.00, 3],
    ['Teclado Notebook Dell', 70.00, 180.00, 6],
    ['Kit ferramentas microreparo', 0, 0, 2],
];
$prodIds = [];
foreach ($produtos as $p) {
    $prodIds[] = $ins("INSERT INTO produtos (empresa_id, nome, unidade, estoque_atual, estoque_minimo, valor_custo, valor_venda, ativo) VALUES (?,?, 'un', ?, 2, ?, ?, 1)",
        [$eid, $p[0], $p[3], $p[1], $p[2]]);
}

// ── Clientes (fictícios, pool maior pra sustentar 2 anos de OS) ──────────────
$primeirosNomes = ['Joao','Maria','Carlos','Ana','Roberto','Fernanda','Lucas','Juliana','Marcos','Patricia',
    'Rafael','Camila','Bruno','Larissa','Diego','Aline','Thiago','Vanessa','Rodrigo','Gabriela',
    'Felipe','Beatriz','Andre','Tatiane','Leonardo','Priscila','Gustavo','Renata','Eduardo','Simone'];
$sobrenomes = ['Silva','Santos','Souza','Rodrigues','Lima','Martins','Ferreira','Alves','Dias','Nunes',
    'Pereira','Oliveira','Costa','Ribeiro','Gomes','Carvalho','Barbosa','Araujo','Melo','Rocha'];
$clientesDados = [];
for ($i = 0; $i < 36; $i++) {
    $nome = $primeirosNomes[$i % count($primeirosNomes)] . ' ' . $sobrenomes[($i * 7) % count($sobrenomes)] . ' ' . $sobrenomes[($i * 3 + 1) % count($sobrenomes)];
    $tel = sprintf('(11) 9%04d-%04d', rand(6000, 9999), rand(1000, 9999));
    $clientesDados[] = [$nome, $tel];
}
$cli = [];
foreach ($clientesDados as $c) {
    $cli[] = $ins("INSERT INTO clientes (empresa_id, tipo, nome, telefone, whatsapp, cidade, uf, origem, status) VALUES (?, 'pf', ?, ?, ?, 'Sao Paulo', 'SP', 'balcao', 'ativo')",
        [$eid, $c[0], $c[1], preg_replace('/\D/', '', $c[1])]);
}

// ── OS + financeiro: ~2 anos de histórico com faturamento mensal expressivo ──
$templatesOS = [
    ['Celular', ['Samsung','Apple','Motorola','Xiaomi'], ['Galaxy A20','Galaxy S21','iPhone 11','iPhone XR','Moto G8','Redmi 9','Galaxy S10'],
        ['Tela quebrada, touch nao responde.','Nao carrega, esquenta na tomada.','Bateria viciada, descarrega rapido.','Sem imagem mas liga.','Molhou, cliente quer avaliacao.','Alto-falante chiando nas ligacoes.'], 120, 550],
    ['TV', ['LG','Samsung','Philco','AOC'], ['43UM7300','50UN7310','PTV32','32S5195'],
        ['Liga mas fica com a tela preta.','Nao liga, LED pisca.','Imagem com listras coloridas.','Sem som, imagem normal.'], 200, 700],
    ['Notebook', ['Dell','Positivo','Lenovo','Acer'], ['Inspiron 15','Motion','Ideapad','Aspire 5'],
        ['Muito lento e desligando sozinho.','Teclado com varias teclas sem funcionar.','Nao da video na tela.','Nao liga de jeito nenhum.'], 180, 850],
    ['Micro-ondas', ['Electrolux','Panasonic','Consul'], ['MEF41','NN-ST25','CMS30'],
        ['Nao esquenta, prato gira normal.','Nao liga.','Prato nao gira.'], 120, 320],
    ['Tablet', ['Samsung','Apple'], ['Tab A7','iPad 9'],
        ['Tela quebrada.','Nao carrega mais.'], 150, 450],
];

$statusPassado = [['Entregue', 90], ['Cancelado', 10]];
$statusAtual = [
    ['Aguardando Diagnostico', 12], ['Em Diagnostico', 12], ['Aguardando Aprovacao', 10],
    ['Aprovado', 8], ['Em Reparo', 16], ['Pronto', 16], ['Entregue', 20], ['Cancelado', 6],
];

function sorteiaPonderado(array $opcoes): string {
    $total = array_sum(array_column($opcoes, 1));
    $r = mt_rand(1, $total);
    $acc = 0;
    foreach ($opcoes as [$nome, $peso]) {
        $acc += $peso;
        if ($r <= $acc) return $nome;
    }
    return $opcoes[0][0];
}

$stmtEquip = $pdo->prepare("INSERT INTO equipamentos (empresa_id, cliente_id, tipo, marca, modelo, estado_entrada, descricao_defeito_cliente, criado_em) VALUES (?,?,?,?,?, 'bom', ?, ?)");
$stmtOS = $pdo->prepare("INSERT INTO ordens_servico (empresa_id, numero, cliente_id, equipamento_id, status_id, tipo_servico, prioridade, defeito_relatado, valor_total, valor_pago, situacao_pagamento, garantia_dias, data_entrada, data_previsao, data_conclusao, token_publico) VALUES (?,?,?,?,?, 'conserto', 'normal', ?, ?, ?, ?, 90, ?, ?, ?, ?)");
$stmtServico = $pdo->prepare("INSERT INTO os_servicos (empresa_id, os_id, descricao, quantidade, valor_unitario, valor_total) VALUES (?,?,?,1,?,?)");
$stmtFin = $pdo->prepare("INSERT INTO fin_lancamentos (empresa_id, conta_id, conta_simples, categoria_id, os_id, cliente_id, tipo, descricao, valor, data_vencimento, data_pagamento, status, forma_pagamento) VALUES (?,?, 'caixa', ?, ?, ?, 'receita', ?, ?, ?, ?, 'pago', 'pix')");
$stmtFinAvulso = $pdo->prepare("INSERT INTO fin_lancamentos (empresa_id, conta_id, conta_simples, categoria_id, tipo, descricao, valor, data_vencimento, data_pagamento, status, forma_pagamento) VALUES (?,?, 'caixa', ?, 'receita', ?, ?, ?, ?, 'pago', ?)");
$stmtDespesa = $pdo->prepare("INSERT INTO fin_lancamentos (empresa_id, conta_id, conta_simples, categoria_id, tipo, descricao, valor, data_vencimento, data_pagamento, status, forma_pagamento) VALUES (?,?, 'caixa', ?, 'despesa', ?, ?, ?, ?, 'pago', 'dinheiro')");

$mesesHistorico = 26; // ~2 anos e alguns meses, pra garantir "pelo menos 2 anos" no grafico
$numOs = 1;
$anoAtual = (int) date('Y');
$mesAtual = (int) date('n');
$diaAtual = (int) date('j');
$totalMesesBase = $anoAtual * 12 + ($mesAtual - 1);

for ($m = $mesesHistorico - 1; $m >= 0; $m--) {
    $tm = $totalMesesBase - $m;
    $anoMes = intdiv($tm, 12);
    $mesMes = ($tm % 12) + 1;
    $diasNoMes = (int) date('t', mktime(0, 0, 0, $mesMes, 1, $anoMes));
    $ehMesAtual = ($m === 0);
    $ultimoDiaValido = $ehMesAtual ? $diaAtual : $diasNoMes;

    $targetReceita = rand(30000, 50000);
    $qtdOS = $ehMesAtual ? rand(35, 55) : rand(55, 85);

    $receitaOS = 0;
    for ($i = 0; $i < $qtdOS; $i++) {
        $tpl = $templatesOS[array_rand($templatesOS)];
        [$tipo, $marcas, $modelos, $defeitos, $vMin, $vMax] = $tpl;
        $marca = $marcas[array_rand($marcas)];
        $modelo = $modelos[array_rand($modelos)];
        $defeito = $defeitos[array_rand($defeitos)];
        $valor = rand($vMin, $vMax);

        $dia = rand(1, max(1, $ultimoDiaValido));
        $entrada = sprintf('%04d-%02d-%02d %02d:%02d:00', $anoMes, $mesMes, $dia, rand(8, 18), rand(0, 59));

        $statusNome = $ehMesAtual ? sorteiaPonderado($statusAtual) : sorteiaPonderado($statusPassado);
        $stTipo = null;
        foreach ($statusDefs as $sd) if ($sd[0] === $statusNome) $stTipo = $sd[3];
        $concluida = in_array($stTipo, ['concluida', 'entregue']);
        $cancelada = ($statusNome === 'Cancelado');
        $pago = $concluida && !$cancelada;

        $cliId = $cli[array_rand($cli)];
        $dataConclusao = $concluida ? date('Y-m-d H:i:s', strtotime($entrada . ' +' . rand(1, 4) . ' days')) : null;
        $numero = str_pad((string) $numOs++, 5, '0', STR_PAD_LEFT);
        $valorOS = $cancelada ? 0 : $valor;

        $stmtEquip->execute([$eid, $cliId, $tipo, $marca, $modelo, $defeito, $entrada]);
        $equipId = (int) $pdo->lastInsertId();

        $stmtOS->execute([$eid, $numero, $cliId, $equipId, $status[$statusNome], $defeito, $valorOS,
            $pago ? $valorOS : 0, $pago ? 'pago' : 'pendente', $entrada,
            date('Y-m-d H:i:s', strtotime($entrada . ' +5 days')), $dataConclusao, bin2hex(random_bytes(16))]);
        $osId = (int) $pdo->lastInsertId();

        if ($valorOS > 0) {
            $stmtServico->execute([$eid, $osId, 'Servico de reparo - ' . $tipo, $valorOS, $valorOS]);
        }
        if ($pago && $valorOS > 0) {
            $dtPag = date('Y-m-d', strtotime($dataConclusao));
            $stmtFin->execute([$eid, $conta, $catRec['Servicos'], $osId, $cliId, 'OS ' . $numero . ' - ' . $tipo, $valorOS, $dtPag, $dtPag]);
            $receitaOS += $valorOS;
        }
    }

    // Completa o faturamento do mes com vendas avulsas de pecas (balcao)
    $falta = $targetReceita - $receitaOS;
    while ($falta > 80) {
        $venda = min($falta, rand(150, 700));
        $dia = rand(1, max(1, $ultimoDiaValido));
        $dt = sprintf('%04d-%02d-%02d', $anoMes, $mesMes, $dia);
        $stmtFinAvulso->execute([$eid, $conta, $catRec['Venda de pecas'], 'Venda balcao - peca avulsa', $venda, $dt, $dt, rand(0, 1) ? 'pix' : 'dinheiro']);
        $falta -= $venda;
    }

    // Despesas do mes (~45-60% do faturamento, pra fluxo de caixa realista)
    $totalDespesas = (int) round($targetReceita * (rand(45, 60) / 100));
    $aluguel = min($totalDespesas, rand(1400, 1900));
    $restante = max($totalDespesas - $aluguel, 400);
    $compraPecas = (int) round($restante * 0.65);
    $contas = $restante - $compraPecas;
    foreach ([
        [$catDes['Aluguel'], 'Aluguel da loja', $aluguel, 5],
        [$catDes['Compra de pecas'], 'Compra de pecas', $compraPecas, 12],
        [$catDes['Contas (luz/net)'], 'Conta de luz + internet', $contas, 18],
    ] as [$catId, $desc, $valorDesp, $diaBase]) {
        if ($valorDesp <= 0) continue;
        $dia = min($diaBase, max(1, $ultimoDiaValido));
        $dt = sprintf('%04d-%02d-%02d', $anoMes, $mesMes, $dia);
        $stmtDespesa->execute([$eid, $conta, $catId, $desc, $valorDesp, $dt, $dt]);
    }
}

echo "DEMO OK: empresa_id=$eid user_id=$uid email=$DEMO_EMAIL\n";
echo "  " . count($cli) . " clientes, " . ($numOs - 1) . " OS ao longo de {$mesesHistorico} meses, " . count($prodIds) . " produtos, financeiro populado (R$30-50 mil/mes).\n";
