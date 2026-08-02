<?php
/**
 * Seed: 600 clientes + 600 OS fictícios aleatórios
 * php database/seeds/002_seed_ficticios.php
 */

define('BASE_PATH', dirname(__DIR__, 2));
$cfg = require BASE_PATH . '/config/database.php';
$dsn = "{$cfg['driver']}:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']};charset={$cfg['charset']}";
$db  = new PDO($dsn, $cfg['username'], $cfg['password'], $cfg['options']);

$empresaId  = 4; // empresa que está em uso
$usuarioId  = 1; // técnico/usuário padrão

/* ── Dados fictícios ─────────────────────────────────────── */

$nomesM = ['Carlos','João','Pedro','Lucas','Gabriel','Rafael','Mateus','Bruno','Felipe','André',
           'Diego','Thiago','Eduardo','Gustavo','Rodrigo','Marcelo','Fernando','Leonardo','Henrique','Alexandre',
           'Roberto','Paulo','Sérgio','Fábio','Renato','Claudinho','Márcio','Wallace','Vinícius','Wellington'];

$nomesF = ['Ana','Maria','Julia','Larissa','Beatriz','Amanda','Fernanda','Camila','Daniela','Vanessa',
           'Patrícia','Sandra','Cristina','Adriana','Letícia','Mariana','Bruna','Isabela','Natália','Carla',
           'Simone','Regina','Rosana','Michele','Viviane','Tatiane','Eliane','Cláudia','Mônica','Débora'];

$sobrenomes = ['Silva','Santos','Oliveira','Souza','Lima','Ferreira','Costa','Pereira','Rodrigues','Almeida',
               'Nascimento','Carvalho','Freitas','Barbosa','Ribeiro','Martins','Rocha','Gomes','Araújo','Correia',
               'Mendes','Cardoso','Teixeira','Dias','Nunes','Pinto','Machado','Azevedo','Castro','Monteiro'];

$cidades = [
  ['São Paulo','SP'],['Rio de Janeiro','RJ'],['Belo Horizonte','MG'],['Salvador','BA'],['Fortaleza','CE'],
  ['Curitiba','PR'],['Manaus','AM'],['Recife','PE'],['Porto Alegre','RS'],['Goiânia','GO'],
  ['Belém','PA'],['Guarulhos','SP'],['Campinas','SP'],['São Luís','MA'],['Maceió','AL'],
  ['Natal','RN'],['Teresina','PI'],['Campo Grande','MS'],['João Pessoa','PB'],['Osasco','SP'],
];

$logradouros = ['Rua das Flores','Av. Brasil','Rua São José','Av. Paulista','Rua 7 de Setembro',
                'Rua da Paz','Av. Getúlio Vargas','Rua XV de Novembro','Av. Independência','Rua Tiradentes',
                'Rua Marechal Deodoro','Av. Dom Pedro','Rua Bela Vista','Av. Presidente Vargas','Rua Ipiranga'];

$marcasEquip = ['Samsung','LG','Sony','Philips','Philco','Positivo','Motorola','Apple','Nokia','Xiaomi',
                'Brastemp','Consul','Electrolux','Panasonic','Midea','TCL','AOC','Dell','HP','Lenovo'];

$tiposEquip  = ['Televisor (TV)','Celular / Smartphone','Notebook / Laptop','Geladeira / Refrigerador',
                'Máquina de Lavar','Micro-ondas','Ar Condicionado','Tablet','Monitor','Impressora',
                'Desktop / PC','Ventilador','Lava-louças','Secadora','Forno Elétrico'];

$modelos = [
  'Samsung'  => ['Galaxy S21','Galaxy A52','Neo QLED 55','Crystal UHD 50','Galaxy Tab A7'],
  'LG'       => ['G8 ThinQ','OLED 65C1','NanoCell 55','K52','Velvet'],
  'Sony'     => ['Xperia 5','Bravia X80J','Bravia KD-55','WH-1000XM4','HT-G700'],
  'Philips'  => ['65PUG6654','50PUG6654','V10','43PFG5102','32PHG5102'],
  'Brastemp' => ['Frost Free 375L','Inverse 460L','Double Drawer','6kg','9kg BWT'],
  'Consul'   => ['Facilite 310L','Bem-Estar 340L','Turbo 11kg','5kg CWT','Duo 460L'],
  'default'  => ['Modelo X1','Modelo Pro','Modelo Plus','Modelo Max','Modelo Lite','Modelo Ultra'],
];

$defeitos = [
  'Televisor (TV)'          => ['Tela apagando','Sem imagem','Sem som','Não liga','Tela com listras','Imagem travando','HDMI sem sinal'],
  'Celular / Smartphone'    => ['Tela quebrada','Não carrega','Câmera não funciona','Sem áudio','Bateria vicia rápido','Touch não responde','Não liga'],
  'Notebook / Laptop'       => ['Não liga','Tela escura','Superaquecendo','Teclado defeituoso','Sem Wi-Fi','HD com defeito','Bateria não carrega'],
  'Geladeira / Refrigerador'=> ['Não gela','Fazendo barulho','Vazando água','Porta sem vedação','Compressor com defeito','Luz interna apagada'],
  'Máquina de Lavar'        => ['Não centrifuga','Vazando água','Não drena','Fazendo barulho','Não liga','Tampa travada'],
  'Micro-ondas'             => ['Não aquece','Prato não gira','Teclado sem resposta','Faiscando','Não liga'],
  'Ar Condicionado'         => ['Não gela','Vazando água','Não liga','Fazendo barulho','Controle sem sinal','Mau cheiro'],
  'default'                 => ['Com defeito','Não funciona','Apresenta falha','Necessita revisão','Parou de funcionar'],
];

$solucoes = [
  'Troca de placa principal','Substituição de capacitor','Troca de tela','Limpeza interna','Troca de bateria',
  'Substituição de compressor','Regulagem de tensão','Troca de fonte','Ajuste de borracha de vedação',
  'Atualização de firmware','Troca de teclado','Substituição de HD','Reparo de solda fria',
  'Troca de sensor de temperatura','Limpeza de serpentina','Substituição de rolamento',
];

$formasPgto = ['dinheiro','pix','cartao_credito','cartao_debito','transferencia'];
$origens    = ['balcao','telefone','whatsapp','indicacao','site'];
$prioridades= ['baixa','normal','normal','normal','alta','urgente'];

/* ── Helpers ─────────────────────────────────────────────── */

function cpf(): string {
  $n = array_map(fn() => rand(0,9), range(1,9));
  $d1 = 0; for($i=0;$i<9;$i++) $d1 += $n[$i]*(10-$i); $d1 = ($d1*10)%11; $d1 = $d1>=10?0:$d1;
  $d2 = 0; for($i=0;$i<9;$i++) $d2 += $n[$i]*(11-$i); $d2 += $d1*2; $d2 = ($d2*10)%11; $d2 = $d2>=10?0:$d2;
  $n[] = $d1; $n[] = $d2;
  return implode('',$n);
}

function fone(): string {
  return '11 9'.rand(1000,9999).'-'.rand(1000,9999);
}

function cep(): string {
  return str_pad(rand(1000000,99999999),8,'0',STR_PAD_LEFT);
}

function rand_data(string $inicio = '-2 years', string $fim = 'now'): string {
  return date('Y-m-d H:i:s', rand(strtotime($inicio), strtotime($fim)));
}

function pick(array $arr) { return $arr[array_rand($arr)]; }

/* ── Status da empresa ───────────────────────────────────── */
$stmtStatus = $db->prepare("SELECT id, tipo FROM os_status WHERE empresa_id = ? ORDER BY ordem");
$stmtStatus->execute([$empresaId]);
$statusList = $stmtStatus->fetchAll();
if (!$statusList) { die("Nenhum status cadastrado para empresa $empresaId\n"); }

$stmtConta = $db->prepare("SELECT id FROM fin_contas WHERE empresa_id = ? LIMIT 1");
$stmtConta->execute([$empresaId]);
$contaId = $stmtConta->fetchColumn() ?: null;

/* ══════════════════════════════════════════════════════════
   CLIENTES
══════════════════════════════════════════════════════════ */
echo "Criando 600 clientes...\n";
$clienteIds = [];

$stmtC = $db->prepare(
  "INSERT INTO clientes (empresa_id,tipo,nome,cpf_cnpj,email,telefone,whatsapp,cep,logradouro,numero,bairro,cidade,uf,origem,status,criado_em)
   VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
);

for ($i = 0; $i < 600; $i++) {
  $sexo    = rand(0,1);
  $prNome  = $sexo ? pick($nomesM) : pick($nomesF);
  $nome    = $prNome . ' ' . pick($sobrenomes) . ' ' . pick($sobrenomes);
  $cidade  = pick($cidades);
  $tipo    = rand(0,9) < 9 ? 'pf' : 'pj';
  $created = rand_data('-2 years');

  $stmtC->execute([
    $empresaId, $tipo, $nome, cpf(),
    strtolower(str_replace(' ','.',explode(' ',$nome)[0])) . rand(1,999) . '@email.com',
    fone(), fone(),
    cep(),
    pick($logradouros), rand(1,9999),
    'Centro', $cidade[0], $cidade[1],
    pick($origens), 'ativo', $created
  ]);
  $clienteIds[] = (int) $db->lastInsertId();
}
echo "✅ 600 clientes criados\n";

/* ══════════════════════════════════════════════════════════
   EQUIPAMENTOS + OS
══════════════════════════════════════════════════════════ */
echo "Criando 600 OS...\n";

// Buscar max numero atual
$stmtMax = $db->prepare("SELECT MAX(CAST(numero AS UNSIGNED)) FROM ordens_servico WHERE empresa_id=?");
$stmtMax->execute([$empresaId]);
$ultimoNum = (int) $stmtMax->fetchColumn();
$digitos   = 6;

$stmtEq = $db->prepare(
  "INSERT INTO equipamentos (empresa_id,cliente_id,tipo,marca,modelo,numero_serie,estado_entrada,descricao_defeito_cliente,criado_em)
   VALUES (?,?,?,?,?,?,?,?,?)"
);

$stmtOs = $db->prepare(
  "INSERT INTO ordens_servico (empresa_id,numero,cliente_id,equipamento_id,status_id,tecnico_id,recepcionista_id,
   prioridade,tipo_servico,defeito_relatado,solucao_aplicada,laudo_tecnico,
   valor_diagnostico,valor_total,valor_pago,situacao_pagamento,garantia_dias,garantia_ate,
   data_entrada,data_previsao,data_conclusao,data_entrega,criado_em)
   VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
);

$stmtHist = $db->prepare(
  "INSERT INTO os_historico (empresa_id,os_id,usuario_id,status_novo_id,descricao,criado_em)
   VALUES (?,?,?,?,?,?)"
);

$stmtFin = $db->prepare(
  "INSERT INTO fin_lancamentos (empresa_id,conta_id,os_id,cliente_id,usuario_id,tipo,descricao,valor,data_vencimento,data_pagamento,status,forma_pagamento)
   VALUES (?,?,?,?,?,'receita',?,?,?,?,?,?)"
);

for ($i = 0; $i < 600; $i++) {
  $ultimoNum++;
  $numero     = str_pad($ultimoNum, $digitos, '0', STR_PAD_LEFT);
  $clienteId  = pick($clienteIds);
  $marca      = pick($marcasEquip);
  $tipo       = pick($tiposEquip);
  $modeloArr  = $modelos[$marca] ?? $modelos['default'];
  $modelo     = pick($modeloArr);
  $defeitoArr = $defeitos[$tipo] ?? $defeitos['default'];
  $defeito    = pick($defeitoArr);
  $status     = pick($statusList);
  $prioridade = pick($prioridades);
  $tipoSvc    = pick(['conserto','conserto','conserto','orcamento','manutencao','garantia']);
  $garantiaDias = pick([30,60,90,90,90,180]);
  $dataEntrada= rand_data('-18 months', '-1 day');
  $dataPrevis = date('Y-m-d H:i:s', strtotime($dataEntrada) + rand(3,15)*86400);
  $valorTotal = round(rand(80,2500) + rand(0,99)/100, 2);
  $criado     = $dataEntrada;

  // Equipamento
  $stmtEq->execute([
    $empresaId, $clienteId, $tipo, $marca, $modelo,
    strtoupper(substr(md5(rand()),0,10)),
    pick(['otimo','bom','regular','ruim']),
    $defeito, $dataEntrada
  ]);
  $equipId = (int) $db->lastInsertId();

  // Definir datas de conclusão/entrega conforme status
  $dataConclusao = null;
  $dataEntrega   = null;
  $situacaoPgto  = 'pendente';
  $valorPago     = 0;
  $garantiaAte   = null;
  $solucao       = null;
  $laudo         = null;

  if (in_array($status['tipo'], ['concluida','entregue'])) {
    $dataConclusao = date('Y-m-d H:i:s', strtotime($dataEntrada) + rand(2,10)*86400);
    $garantiaAte   = date('Y-m-d', strtotime($dataConclusao) + $garantiaDias*86400);
    $solucao       = pick($solucoes);
    $laudo         = 'Equipamento apresentou ' . strtolower($defeito) . '. ' . $solucao . '.';
    $situacaoPgto  = pick(['pago','pago','pago','parcial','pendente']);
    $valorPago     = $situacaoPgto === 'pago' ? $valorTotal : ($situacaoPgto === 'parcial' ? round($valorTotal * rand(30,70)/100, 2) : 0);
    if ($status['tipo'] === 'entregue') {
      $dataEntrega = date('Y-m-d H:i:s', strtotime($dataConclusao) + rand(0,3)*86400);
    }
  } elseif ($status['tipo'] === 'cancelada') {
    $situacaoPgto = 'pendente';
    $valorTotal   = 0;
  }

  // OS
  $stmtOs->execute([
    $empresaId, $numero, $clienteId, $equipId, $status['id'],
    $usuarioId, $usuarioId,
    $prioridade, $tipoSvc, $defeito, $solucao, $laudo,
    0, $valorTotal, $valorPago, $situacaoPgto,
    $garantiaDias, $garantiaAte,
    $dataEntrada, $dataPrevis, $dataConclusao, $dataEntrega, $criado
  ]);
  $osId = (int) $db->lastInsertId();

  // Histórico
  $stmtHist->execute([$empresaId, $osId, $usuarioId, $status['id'], 'OS criada.', $dataEntrada]);

  // Lançamento financeiro para OS pagas
  if ($situacaoPgto === 'pago' && $valorPago > 0 && $contaId) {
    $dataPgto = $dataConclusao ? date('Y-m-d', strtotime($dataConclusao)) : date('Y-m-d');
    $stmtFin->execute([
      $empresaId, $contaId, $osId, $clienteId, $usuarioId,
      'OS: ' . $numero . ' — ' . $defeito,
      $valorPago, $dataPgto, $dataPgto, 'pago', pick($formasPgto)
    ]);
  }
}

echo "✅ 600 OS criadas\n";

$totalC = $db->query("SELECT COUNT(*) FROM clientes WHERE empresa_id=$empresaId")->fetchColumn();
$totalO = $db->query("SELECT COUNT(*) FROM ordens_servico WHERE empresa_id=$empresaId")->fetchColumn();
echo "\nTotal clientes empresa $empresaId: $totalC";
echo "\nTotal OS empresa $empresaId: $totalO\n";
echo "\nSeed concluído com sucesso! 🎉\n";
