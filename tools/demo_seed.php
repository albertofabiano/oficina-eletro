<?php
/**
 * Seed + reset da EMPRESA DEMONSTRAÇÃO do FixaOS.
 * Cria (idempotente) a empresa/usuário demo e popula com dados de exemplo realistas.
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

// ── Clientes (fictícios) ─────────────────────────────────────────────────────
$clientesDados = [
    ['Joao Pereira da Silva', '(11) 98877-1122'],
    ['Maria Oliveira Santos', '(11) 99654-3321'],
    ['Carlos Eduardo Souza', '(11) 97712-8890'],
    ['Ana Paula Rodrigues', '(11) 98123-4567'],
    ['Roberto Almeida Lima', '(11) 99001-2233'],
    ['Fernanda Costa Martins', '(11) 98456-7788'],
    ['Lucas Gabriel Ferreira', '(11) 97345-6612'],
    ['Juliana Ribeiro Alves', '(11) 99887-0011'],
    ['Marcos Antonio Dias', '(11) 98567-4432'],
    ['Patricia Gomes Nunes', '(11) 97234-9988'],
];
$cli = [];
foreach ($clientesDados as $c) {
    $cli[] = $ins("INSERT INTO clientes (empresa_id, tipo, nome, telefone, whatsapp, cidade, uf, origem, status) VALUES (?, 'pf', ?, ?, ?, 'Sao Paulo', 'SP', 'balcao', 'ativo')",
        [$eid, $c[0], $c[1], preg_replace('/\D/', '', $c[1])]);
}

// ── OS (variadas) ────────────────────────────────────────────────────────────
// [tipoEquip, marca, modelo, defeito, statusNome, valor, pago?]
$osDefs = [
    ['Celular','Samsung','Galaxy A20','Tela quebrada, touch nao responde no canto direito.','Pronto',350.00,true],
    ['Celular','Apple','iPhone 11','Nao carrega. Cliente diz que esquenta ao ligar na tomada.','Em Reparo',260.00,false],
    ['TV','LG','43UM7300','Liga mas fica com a tela preta, so aparece o som.','Aguardando Aprovacao',400.00,false],
    ['Notebook','Dell','Inspiron 15','Muito lento e desligando sozinho apos alguns minutos.','Em Diagnostico',180.00,false],
    ['Celular','Motorola','Moto G8','Trocar conector de carga, entrada frouxa.','Entregue',150.00,true],
    ['Celular','Xiaomi','Redmi 9','Bateria viciada, descarrega rapido.','Pronto',230.00,true],
    ['TV','Philco','PTV32','Nao liga, LED da frente pisca 3 vezes.','Aguardando Diagnostico',0,false],
    ['Notebook','Positivo','Motion','Teclado com varias teclas sem funcionar.','Aprovado',280.00,false],
    ['Celular','Apple','iPhone XR','Cliente molhou o aparelho, quer avaliacao.','Em Diagnostico',0,false],
    ['Micro-ondas','Electrolux','MEF41','Nao esquenta, prato gira normal.','Entregue',180.00,true],
    ['Celular','Samsung','Galaxy S10','Camera traseira embacada apos queda.','Cancelado',0,false],
    ['Notebook','Dell','Latitude','Nao da video na tela, na TV externa funciona.','Aguardando Aprovacao',560.00,false],
    ['Celular','Motorola','Edge 20','Alto-falante chiando nas ligacoes.','Pronto',190.00,true],
    ['TV','LG','50UN7310','Trocar fonte, aparelho desliga sozinho.','Em Reparo',350.00,false],
    ['Celular','Xiaomi','Note 10','Tela com manchas roxas se espalhando.','Entregue',280.00,true],
];

$numOs = 1;
foreach ($osDefs as $i => $o) {
    $cliId = $cli[$i % count($cli)];
    $diasAtras = rand(1, 28);
    $entrada = date('Y-m-d H:i:s', strtotime("-{$diasAtras} days"));
    $catEq = null;
    // equipamento
    $equipId = $ins("INSERT INTO equipamentos (empresa_id, cliente_id, tipo, marca, modelo, estado_entrada, descricao_defeito_cliente, criado_em) VALUES (?,?,?,?,?, 'bom', ?, ?)",
        [$eid, $cliId, $o[0], $o[1], $o[2], $o[3], $entrada]);
    $stId = $status[$o[4]];
    $stTipo = null;
    foreach ($statusDefs as $sd) if ($sd[0] === $o[4]) $stTipo = $sd[3];
    $concluida = in_array($stTipo, ['concluida','entregue']);
    $pago = $o[6];
    $numero = str_pad((string) $numOs++, 4, '0', STR_PAD_LEFT);
    $dataConclusao = $concluida ? date('Y-m-d H:i:s', strtotime($entrada . ' +' . rand(1, 5) . ' days')) : null;
    $osId = $ins(
        "INSERT INTO ordens_servico (empresa_id, numero, cliente_id, equipamento_id, status_id, tipo_servico, prioridade, defeito_relatado, valor_total, valor_pago, situacao_pagamento, garantia_dias, data_entrada, data_previsao, data_conclusao, token_publico)
         VALUES (?,?,?,?,?, 'conserto', 'normal', ?, ?, ?, ?, 90, ?, ?, ?, ?)",
        [$eid, $numero, $cliId, $equipId, $stId, $o[3], $o[5], $pago ? $o[5] : 0, $pago ? 'pago' : 'pendente',
         $entrada, date('Y-m-d H:i:s', strtotime($entrada . ' +5 days')), $dataConclusao, bin2hex(random_bytes(16))]);
    // serviço da OS
    if ($o[5] > 0) {
        $ins("INSERT INTO os_servicos (empresa_id, os_id, descricao, quantidade, valor_unitario, valor_total) VALUES (?,?,?,1,?,?)",
            [$eid, $osId, 'Servico de reparo - ' . $o[0], $o[5], $o[5]]);
    }
    // financeiro para OS paga
    if ($pago && $o[5] > 0) {
        $ins("INSERT INTO fin_lancamentos (empresa_id, conta_id, conta_simples, categoria_id, os_id, cliente_id, tipo, descricao, valor, data_vencimento, data_pagamento, status, forma_pagamento) VALUES (?,?, 'caixa', ?, ?, ?, 'receita', ?, ?, ?, ?, 'pago', 'pix')",
            [$eid, $conta, $catRec['Servicos'], $osId, $cliId, 'OS ' . $numero . ' - ' . $o[0], $o[5], date('Y-m-d', strtotime($dataConclusao)), date('Y-m-d', strtotime($dataConclusao))]);
    }
}

// ── Algumas despesas para o fluxo ficar realista ─────────────────────────────
$despesas = [
    ['Compra de pecas', $catDes['Compra de pecas'], 620.00, 6],
    ['Aluguel da loja', $catDes['Aluguel'], 1200.00, 10],
    ['Conta de luz + internet', $catDes['Contas (luz/net)'], 340.00, 8],
];
foreach ($despesas as $d) {
    $dt = date('Y-m-d', strtotime("-{$d[3]} days"));
    $ins("INSERT INTO fin_lancamentos (empresa_id, conta_id, conta_simples, categoria_id, tipo, descricao, valor, data_vencimento, data_pagamento, status, forma_pagamento) VALUES (?,?, 'caixa', ?, 'despesa', ?, ?, ?, ?, 'pago', 'dinheiro')",
        [$eid, $conta, $d[1], $d[0], $d[2], $dt, $dt]);
}

echo "DEMO OK: empresa_id=$eid user_id=$uid email=$DEMO_EMAIL\n";
echo "  " . count($cli) . " clientes, " . count($osDefs) . " OS, " . count($prodIds) . " produtos, financeiro populado.\n";
