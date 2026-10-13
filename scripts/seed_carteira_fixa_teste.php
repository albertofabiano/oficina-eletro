<?php
/**
 * Popula uma conta de teste da Carteira Fixa com ~6 meses de histórico realista: salário
 * mensal (~R$5.000, com pequena variação), contas fixas (luz, internet, celular, assinaturas
 * de streaming), compras no dia a dia (mercado, transporte, farmácia, lazer) e compras parceladas
 * no cartão de crédito (loja de roupas) — misturando as formas de pagamento do próprio motor
 * de datas da Carteira Fixa (ver app/Services/Fixa/FormaPagamentoService.php e
 * ROTEIRO-carteira-fixa.md): Pix, Débito, Boleto (todas "simples"), Cartão de crédito (joga
 * pro vencimento da fatura) — e uma fração dos lançamentos marcada como origem="foto",
 * simulando leitura de comprovante por IA (sem chamar a API de verdade — só o campo `origem`
 * fica marcado, igual ficaria se tivesse passado pelo scanner).
 *
 * Cada lançamento passa pelo MESMO FormaPagamentoService::resolverParcelas() que o controller
 * de verdade usa (FinanceiroPessoalController::salvar()) — não reimplementa a matemática de
 * datas, só monta o INSERT com o resultado, exatamente como o fluxo real faz.
 *
 * Por padrão roda em modo SIMULAÇÃO (não grava nada, só mostra o resumo do que seria criado).
 * Pra gravar:
 *   php scripts/seed_carteira_fixa_teste.php --usuario=ID --aplicar
 *   php scripts/seed_carteira_fixa_teste.php --email=fulano@exemplo.com --aplicar
 *
 * Opções:
 *   --usuario=ID    usuario_id de quem vai receber os dados (login dono da conta de teste)
 *   --email=E       alternativa ao --usuario — resolve o id pelo e-mail de login
 *   --perfil=ID     força o perfil (financeiro_pessoal_perfis.id) — só necessário se o usuário
 *                   tiver mais de um perfil não-arquivado (o script lista e pede pra escolher)
 *   --meses=N       quantos meses de histórico gerar, terminando no mês atual (padrão 6)
 *   --aplicar       grava de verdade — sem isso, só simula e mostra o resumo
 *
 * Só funciona pra perfil tipo="pf" (pessoa física) — o mapeamento de categorias usado aqui é o
 * PADRAO_PF de PerfilService; um perfil "pj" exigiria outro conjunto de categorias/descrições,
 * fora do pedido original.
 *
 * Pra desfazer depois (ajuste os valores pelos impressos no resumo final):
 *   DELETE FROM financeiro_pessoal_lancamentos WHERE id BETWEEN {MIN_ID} AND {MAX_ID};
 */

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});
require BASE_PATH . '/app/Helpers/functions.php';

use App\Core\DB;
use App\Services\Fixa\PerfilService;
use App\Services\Fixa\FormaPagamentoService as FP;

$aplicar = in_array('--aplicar', $argv, true);
$argOpt = function (string $nome, $default) use ($argv) {
    foreach ($argv as $a) {
        if (str_starts_with($a, "--{$nome}=")) return substr($a, strlen($nome) + 3);
    }
    return $default;
};
$usuarioArg = $argOpt('usuario', null);
$emailArg   = $argOpt('email', null);
$perfilArg  = $argOpt('perfil', null);
$meses      = max(1, min(24, (int) $argOpt('meses', 6)));

echo ($aplicar ? "MODO APLICAR — vai gravar de verdade no banco.\n" : "MODO SIMULAÇÃO — nada será gravado (rode com --aplicar pra gravar de verdade).\n");
echo str_repeat('-', 78) . "\n";

$db = DB::pdo();

// ---------------------------------------------------------------------------------------
// Resolve usuário
// ---------------------------------------------------------------------------------------
if ($usuarioArg === null && $emailArg === null) {
    fwrite(STDERR, "Informe --usuario=ID ou --email=E do dono da conta de teste.\n");
    exit(1);
}
if ($emailArg !== null) {
    $st = $db->prepare("SELECT id, nome, email FROM usuarios WHERE email = ? LIMIT 1");
    $st->execute([$emailArg]);
    $usuario = $st->fetch();
    if (!$usuario) { fwrite(STDERR, "Nenhum usuário com e-mail '{$emailArg}'.\n"); exit(1); }
} else {
    $st = $db->prepare("SELECT id, nome, email FROM usuarios WHERE id = ? LIMIT 1");
    $st->execute([(int) $usuarioArg]);
    $usuario = $st->fetch();
    if (!$usuario) { fwrite(STDERR, "Usuário #{$usuarioArg} não encontrado.\n"); exit(1); }
}
$uid = (int) $usuario['id'];
echo "Usuário: #{$uid} — {$usuario['nome']} ({$usuario['email']})\n";

// ---------------------------------------------------------------------------------------
// Resolve perfil (sempre precisa existir — não cria perfil novo, só usa o que já tem)
// ---------------------------------------------------------------------------------------
if ($perfilArg !== null) {
    $perfil = PerfilService::pertenceAoUsuario($db, (int) $perfilArg, $uid);
    if (!$perfil) { fwrite(STDERR, "Perfil #{$perfilArg} não existe ou não pertence a este usuário.\n"); exit(1); }
} else {
    $perfis = PerfilService::perfisDoUsuario($db, $uid, false);
    if (!$perfis) { fwrite(STDERR, "Usuário ainda não tem nenhum perfil na Carteira Fixa — acesse /financeiro-pessoal pelo menos uma vez antes de rodar este script.\n"); exit(1); }
    if (count($perfis) > 1) {
        fwrite(STDERR, "Mais de um perfil — escolha com --perfil=ID:\n");
        foreach ($perfis as $p) fwrite(STDERR, "  #{$p['id']} — {$p['nome']} ({$p['tipo']})\n");
        exit(1);
    }
    $perfil = $perfis[0];
}
$perfilId = (int) $perfil['id'];
echo "Perfil: #{$perfilId} — {$perfil['nome']} (tipo={$perfil['tipo']})\n";

if ($perfil['tipo'] !== 'pf') {
    fwrite(STDERR, "Este script só sabe gerar dados pra perfil tipo \"pf\" (pessoa física) — perfil #{$perfilId} é \"{$perfil['tipo']}\".\n");
    exit(1);
}

// ---------------------------------------------------------------------------------------
// Categorias (idempotente — só semeia o que faltar, não duplica)
// ---------------------------------------------------------------------------------------
if ($aplicar) {
    $categorias = PerfilService::categoriasDoPerfil($db, $perfilId, 'pf');
} else {
    // Em simulação não grava categoria nenhuma — só confere que as chaves que o script usa
    // existem na lista padrão (PADRAO_PF), pra não prometer um mapeamento que não bate depois.
    $categorias = array_fill_keys(
        ['moradia', 'assinaturas', 'alimentacao', 'transporte', 'compras', 'saude', 'lazer', 'educacao', 'salario', 'outras_receitas'],
        true
    );
}
foreach (['moradia', 'assinaturas', 'alimentacao', 'transporte', 'compras', 'saude', 'lazer', 'educacao', 'salario'] as $chaveNecessaria) {
    if (!isset($categorias[$chaveNecessaria])) {
        fwrite(STDERR, "Categoria obrigatória \"{$chaveNecessaria}\" não existe neste perfil (e não bateu nem como padrão PF) — rode sem --aplicar primeiro pra conferir, ou crie a categoria manualmente em Categorias.\n");
        exit(1);
    }
}

// ---------------------------------------------------------------------------------------
// Conta padrão do perfil (sempre deve existir — toda criação de perfil já cria uma)
// ---------------------------------------------------------------------------------------
$contas = PerfilService::contasDoPerfil($db, $perfilId);
if (!$contas) { fwrite(STDERR, "Perfil #{$perfilId} não tem nenhuma conta cadastrada — crie uma conta antes de rodar este script.\n"); exit(1); }
$contaPadrao = null;
foreach ($contas as $c) { if ((int) $c['padrao'] === 1) { $contaPadrao = $c; break; } }
if (!$contaPadrao) $contaPadrao = $contas[0];
$contaId = (int) $contaPadrao['id'];
echo "Conta: #{$contaId} — {$contaPadrao['nome']}\n";

// ---------------------------------------------------------------------------------------
// Formas de pagamento — reaproveita as que já existem (Pix/Dinheiro/Débito já nascem com o
// perfil); cria só o que falta: Cartão de crédito e Boleto, pedidos explicitamente pelo
// usuário ("gastos em cartão de crédito, pix, boleto"). Em simulação, usa valores de preview
// (não grava nada) só pra calcular as datas de exemplo certinho.
// ---------------------------------------------------------------------------------------
$formasNecessarias = [
    'Pix'              => ['tipo' => 'simples', 'direcao' => 'ambas', 'dia_fechamento' => null, 'dia_vencimento' => null, 'prazo_dias' => null],
    'Débito'           => ['tipo' => 'simples', 'direcao' => 'saida', 'dia_fechamento' => null, 'dia_vencimento' => null, 'prazo_dias' => null],
    'Boleto'           => ['tipo' => 'simples', 'direcao' => 'saida', 'dia_fechamento' => null, 'dia_vencimento' => null, 'prazo_dias' => null],
    'Cartão de crédito'=> ['tipo' => 'cartao',  'direcao' => 'saida', 'dia_fechamento' => 5,    'dia_vencimento' => 12,   'prazo_dias' => null],
];

$stFormas = $db->prepare("SELECT id, nome, tipo, dia_fechamento, dia_vencimento, prazo_dias, usos FROM financeiro_pessoal_formas_pagamento WHERE perfil_id = ?");
$stFormas->execute([$perfilId]);
$formasExistentes = [];
foreach ($stFormas->fetchAll() as $f) { $formasExistentes[$f['nome']] = $f; }

$formas = []; // nome => array com id (null em simulação) + tipo/fechamento/vencimento/prazo
foreach ($formasNecessarias as $nome => $cfg) {
    if (isset($formasExistentes[$nome])) {
        $formas[$nome] = $formasExistentes[$nome];
        continue;
    }
    if ($aplicar) {
        $db->prepare(
            "INSERT INTO financeiro_pessoal_formas_pagamento (usuario_id, perfil_id, nome, tipo, direcao, dia_fechamento, dia_vencimento, prazo_dias)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([$uid, $perfilId, $nome, $cfg['tipo'], $cfg['direcao'], $cfg['dia_fechamento'], $cfg['dia_vencimento'], $cfg['prazo_dias']]);
        $novoId = (int) $db->lastInsertId();
        $formas[$nome] = ['id' => $novoId, 'nome' => $nome, 'tipo' => $cfg['tipo'], 'dia_fechamento' => $cfg['dia_fechamento'], 'dia_vencimento' => $cfg['dia_vencimento'], 'prazo_dias' => $cfg['prazo_dias'], 'usos' => 0];
        echo "Forma de pagamento criada: \"{$nome}\" (#{$novoId})\n";
    } else {
        $formas[$nome] = ['id' => null, 'nome' => $nome, 'tipo' => $cfg['tipo'], 'dia_fechamento' => $cfg['dia_fechamento'], 'dia_vencimento' => $cfg['dia_vencimento'], 'prazo_dias' => $cfg['prazo_dias'], 'usos' => 0];
        echo "[simulação] criaria a forma de pagamento \"{$nome}\" ({$cfg['tipo']})\n";
    }
}
echo str_repeat('-', 78) . "\n";

// ---------------------------------------------------------------------------------------
// Monta o plano de lançamentos (em memória — não escreve nada ainda), mês a mês, terminando
// no mês atual. Cada item: ['descricao','categoria','valor','tipo','forma','parcelas',
// 'dataEvento','origem'].
// ---------------------------------------------------------------------------------------
$hoje = new DateTime(date('Y-m-d'));
$mesesRange = [];
for ($i = $meses - 1; $i >= 0; $i--) {
    $mesesRange[] = (clone $hoje)->modify("first day of -{$i} month");
}

$pool = [
    'alimentacao_merc' => ['Supermercado Extra', 'Mercado Pão de Açúcar', 'Supermercado Dia', 'Hortifruti do Bairro'],
    'alimentacao_rest' => ['iFood', 'Restaurante Sabor Caseiro', 'Padaria do Bairro', 'Cafeteria Central'],
    'transporte'       => ['Uber', 'Posto Shell — combustível', '99 Pop', 'Estacionamento Shopping'],
    'compras'          => ['Renner', 'Zara', 'C&A', 'Riachuelo'],
    'saude'             => ['Drogaria São Paulo', 'Farmácia Pague Menos'],
    'lazer'             => ['Cinemark', 'Livraria Cultura', 'Ingresso show'],
    'educacao'          => ['Curso Udemy', 'Curso de inglês — mensalidade'],
];
$rand = fn(array $a) => $a[array_rand($a)];
$money = fn(float $min, float $max) => round($min + mt_rand() / mt_getrandmax() * ($max - $min), 2);
// ~35% dos lançamentos simulam leitura de comprovante (origem="foto") em vez de digitado na mão.
$origemAleatoria = fn() => (mt_rand(1, 100) <= 35) ? 'foto' : 'manual';

$plano = [];
foreach ($mesesRange as $inicioMes) {
    $ano = (int) $inicioMes->format('Y');
    $mesNum = (int) $inicioMes->format('n');
    $diaMax = (int) $inicioMes->format('t');
    $dataNoMes = fn(int $dia) => sprintf('%04d-%02d-%02d', $ano, $mesNum, min($dia, $diaMax));

    // --- Receita: salário, sempre por Pix, todo início de mês ---
    $plano[] = [
        'descricao' => 'Salário mensal', 'categoria' => 'salario', 'tipo' => 'receita',
        'valor' => $money(4750, 5250), 'forma' => 'Pix', 'parcelas' => 1,
        'dataEvento' => $dataNoMes(5), 'origem' => 'manual',
    ];

    // --- Contas fixas mensais ---
    $plano[] = ['descricao' => 'Conta de luz — Enel', 'categoria' => 'moradia', 'tipo' => 'despesa',
        'valor' => $money(140, 270), 'forma' => 'Boleto', 'parcelas' => 1, 'dataEvento' => $dataNoMes(10), 'origem' => $origemAleatoria()];
    $plano[] = ['descricao' => 'Internet — Vivo Fibra', 'categoria' => 'assinaturas', 'tipo' => 'despesa',
        'valor' => $money(90, 140), 'forma' => 'Débito', 'parcelas' => 1, 'dataEvento' => $dataNoMes(15), 'origem' => $origemAleatoria()];
    $plano[] = ['descricao' => 'Celular — Vivo Controle', 'categoria' => 'assinaturas', 'tipo' => 'despesa',
        'valor' => $money(55, 95), 'forma' => 'Débito', 'parcelas' => 1, 'dataEvento' => $dataNoMes(20), 'origem' => $origemAleatoria()];
    $plano[] = ['descricao' => $rand(['Netflix', 'Spotify Premium', 'Amazon Prime']), 'categoria' => 'assinaturas', 'tipo' => 'despesa',
        'valor' => $money(25, 55), 'forma' => 'Cartão de crédito', 'parcelas' => 1, 'dataEvento' => $dataNoMes(8), 'origem' => 'manual'];

    // --- Alimentação: 2-4 supermercados + 1-2 restaurante/delivery ---
    foreach (range(1, mt_rand(2, 4)) as $_) {
        $plano[] = ['descricao' => $rand($pool['alimentacao_merc']), 'categoria' => 'alimentacao', 'tipo' => 'despesa',
            'valor' => $money(90, 420), 'forma' => $rand(['Pix', 'Débito', 'Cartão de crédito']), 'parcelas' => 1,
            'dataEvento' => $dataNoMes(mt_rand(1, $diaMax)), 'origem' => $origemAleatoria()];
    }
    foreach (range(1, mt_rand(1, 2)) as $_) {
        $plano[] = ['descricao' => $rand($pool['alimentacao_rest']), 'categoria' => 'alimentacao', 'tipo' => 'despesa',
            'valor' => $money(28, 95), 'forma' => $rand(['Pix', 'Cartão de crédito']), 'parcelas' => 1,
            'dataEvento' => $dataNoMes(mt_rand(1, $diaMax)), 'origem' => 'manual'];
    }

    // --- Transporte: 3-6 lançamentos ---
    foreach (range(1, mt_rand(3, 6)) as $_) {
        $plano[] = ['descricao' => $rand($pool['transporte']), 'categoria' => 'transporte', 'tipo' => 'despesa',
            'valor' => $money(25, 150), 'forma' => $rand(['Pix', 'Débito']), 'parcelas' => 1,
            'dataEvento' => $dataNoMes(mt_rand(1, $diaMax)), 'origem' => $origemAleatoria()];
    }

    // --- Compras (roupas) — nem todo mês, às vezes parcelado no cartão ---
    if (mt_rand(1, 100) <= 65) {
        foreach (range(1, mt_rand(1, 2)) as $_) {
            $parc = mt_rand(1, 100) <= 45 ? mt_rand(2, 3) : 1;
            $plano[] = ['descricao' => $rand($pool['compras']), 'categoria' => 'compras', 'tipo' => 'despesa',
                'valor' => $money(90, 380), 'forma' => 'Cartão de crédito', 'parcelas' => $parc,
                'dataEvento' => $dataNoMes(mt_rand(1, $diaMax)), 'origem' => 'manual'];
        }
    }

    // --- Saúde (farmácia) — 0-2 ---
    foreach (range(1, mt_rand(0, 2)) as $_) {
        $plano[] = ['descricao' => $rand($pool['saude']), 'categoria' => 'saude', 'tipo' => 'despesa',
            'valor' => $money(20, 130), 'forma' => $rand(['Pix', 'Débito']), 'parcelas' => 1,
            'dataEvento' => $dataNoMes(mt_rand(1, $diaMax)), 'origem' => $origemAleatoria()];
    }

    // --- Lazer — 0-2 ---
    foreach (range(1, mt_rand(0, 2)) as $_) {
        $plano[] = ['descricao' => $rand($pool['lazer']), 'categoria' => 'lazer', 'tipo' => 'despesa',
            'valor' => $money(30, 150), 'forma' => $rand(['Pix', 'Cartão de crédito']), 'parcelas' => 1,
            'dataEvento' => $dataNoMes(mt_rand(1, $diaMax)), 'origem' => 'manual'];
    }

    // --- Educação — ocasional ---
    if (mt_rand(1, 100) <= 35) {
        $plano[] = ['descricao' => $rand($pool['educacao']), 'categoria' => 'educacao', 'tipo' => 'despesa',
            'valor' => $money(80, 300), 'forma' => $rand(['Pix', 'Boleto']), 'parcelas' => 1,
            'dataEvento' => $dataNoMes(mt_rand(1, $diaMax)), 'origem' => 'manual'];
    }
}

// ---------------------------------------------------------------------------------------
// Resolve cada item do plano em parcelas de verdade via FormaPagamentoService::resolverParcelas()
// — MESMA função que o controller real usa, sem reimplementar a matemática de datas aqui.
// ---------------------------------------------------------------------------------------
$linhas = []; // cada linha já pronta pro INSERT (ou só exibição, em simulação)
$totalReceitas = 0.0;
$totalDespesas = 0.0;
$porOrigem = ['manual' => 0, 'foto' => 0];
$porCategoria = [];
$grupoCompra = 0; // id sintético por ITEM do plano (não por parcela) — usado só pra religar as
                   // parcelas de uma mesma compra depois do usort() abaixo embaralhar a ordem

foreach ($plano as $item) {
    $forma = $formas[$item['forma']];
    $resolvidas = FP::resolverParcelas($forma, $item['dataEvento'], $item['valor'], $item['parcelas']);
    $grupoCompra++;

    foreach ($resolvidas as $i => $p) {
        $descParcela = $item['parcelas'] > 1 ? $item['descricao'] . ' (' . $p['parcela'] . '/' . $p['parcelas'] . ')' : $item['descricao'];
        $jaPassou = $p['data'] <= $hoje->format('Y-m-d');
        $linhas[] = [
            'descricao'   => $descParcela,
            'categoria'   => $item['categoria'],
            'tipo'        => $item['tipo'],
            'valor'       => round($p['valor'], 2),
            'dataHora'    => $p['data'] . ' ' . sprintf('%02d:%02d:00', mt_rand(8, 21), mt_rand(0, 59)),
            'dataEvento'  => $item['dataEvento'],
            'vencimento'  => $jaPassou ? null : $p['data'],
            'pagoEm'      => $jaPassou ? $p['data'] : null,
            'formaId'     => $forma['id'],
            'formaNome'   => $forma['nome'],
            'origem'      => $item['origem'],
            'ehPrimeira'  => $i === 0,
            'grupo'       => $grupoCompra,
        ];
        if ($item['tipo'] === 'receita') { $totalReceitas += $p['valor']; } else { $totalDespesas += $p['valor']; }
        $porOrigem[$item['origem']]++;
        if ($item['tipo'] === 'despesa') {
            $porCategoria[$item['categoria']] = ($porCategoria[$item['categoria']] ?? 0) + $p['valor'];
        }
    }
}

// Ordena por data pra gravar em ordem cronológica (mais parecido com uso real) — a parcela 1
// de um parcelamento SEMPRE cai na data mais cedo dentro do próprio grupo (resolverParcelas()
// sempre anda pra frente no tempo, nunca pra trás), então ela sempre aparece antes das
// parcelas 2+ do mesmo grupo mesmo depois deste sort — é o que garante que $grupoParaId
// abaixo já esteja preenchido quando uma parcela 2+ precisar dele.
usort($linhas, fn($a, $b) => $a['dataHora'] <=> $b['dataHora']);

echo count($linhas) . " lançamento(s) (incluindo parcelas) preparado(s) para {$meses} mês(es), de " . $mesesRange[0]->format('m/Y') . " até " . end($mesesRange)->format('m/Y') . ".\n";
echo "Receitas: R$ " . number_format($totalReceitas, 2, ',', '.') . "   Despesas: R$ " . number_format($totalDespesas, 2, ',', '.') . "   Saldo: R$ " . number_format($totalReceitas - $totalDespesas, 2, ',', '.') . "\n";
echo "Média mensal de receita: R$ " . number_format($totalReceitas / $meses, 2, ',', '.') . "\n";
echo "Origem: " . $porOrigem['manual'] . " manual · " . $porOrigem['foto'] . " foto (leitura de comprovante simulada)\n";
echo "Despesas por categoria:\n";
arsort($porCategoria);
foreach ($porCategoria as $cat => $val) {
    echo "  - {$cat}: R$ " . number_format($val, 2, ',', '.') . "\n";
}
echo str_repeat('-', 78) . "\n";

if (!$aplicar) {
    echo "Amostra dos 10 primeiros lançamentos:\n";
    foreach (array_slice($linhas, 0, 10) as $l) {
        $status = $l['pagoEm'] ? 'pago' : 'pendente';
        echo "  " . substr($l['dataHora'], 0, 10) . " · {$l['descricao']} · R$ " . number_format($l['valor'], 2, ',', '.') . " · {$l['formaNome']} · {$status} · origem={$l['origem']}\n";
    }
    echo "\nRode com --aplicar pra gravar de verdade.\n";
    exit(0);
}

// ---------------------------------------------------------------------------------------
// Grava de verdade — mesma sequência do controller real: INSERT, se for a 1ª parcela grava
// compra_id = próprio id, incrementa usos da forma, registra o aprendizado por beneficiário.
// ---------------------------------------------------------------------------------------
$insert = $db->prepare(
    "INSERT INTO financeiro_pessoal_lancamentos
        (usuario_id, perfil_id, conta_id, forma_pagamento_id, tipo, categoria, descricao, valor,
         data_hora, data_evento, vencimento, pago_em, origem, compra_id, hora_informada)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)"
);

$idsGerados = [];
$usosForma  = [];
$grupoParaId = []; // grupo (item do plano) => id da parcela 1, pra religar as parcelas 2+
foreach ($linhas as $l) {
    $insert->execute([
        $uid, $perfilId, $contaId, $l['formaId'], $l['tipo'], $l['categoria'], $l['descricao'], $l['valor'],
        $l['dataHora'], $l['dataEvento'], $l['vencimento'], $l['pagoEm'], $l['origem'], null,
    ]);
    $novoId = (int) $db->lastInsertId();
    if ($l['ehPrimeira']) {
        $db->prepare("UPDATE financeiro_pessoal_lancamentos SET compra_id = ? WHERE id = ?")->execute([$novoId, $novoId]);
        $grupoParaId[$l['grupo']] = $novoId;
    } else {
        // parcela 2+ da mesma compra: compra_id aponta pro id da parcela 1 do MESMO grupo —
        // sempre já resolvido a esta altura, ver comentário no usort() acima.
        $db->prepare("UPDATE financeiro_pessoal_lancamentos SET compra_id = ? WHERE id = ?")->execute([$grupoParaId[$l['grupo']], $novoId]);
    }
    $idsGerados[] = $novoId;

    if ($l['formaId']) { $usosForma[$l['formaId']] = ($usosForma[$l['formaId']] ?? 0) + 1; }
    financeiro_pessoal_aprender_upsert($db, $uid, $perfilId, $l['descricao'], $l['categoria'], $contaId, $l['formaId']);
}

foreach ($usosForma as $formaId => $qtd) {
    $db->prepare("UPDATE financeiro_pessoal_formas_pagamento SET usos = usos + ? WHERE id = ?")->execute([$qtd, $formaId]);
}

$minId = min($idsGerados);
$maxId = max($idsGerados);

echo count($idsGerados) . " lançamento(s) gravado(s) com sucesso (ids {$minId}–{$maxId}).\n";
echo "Pra desfazer: DELETE FROM financeiro_pessoal_lancamentos WHERE id BETWEEN {$minId} AND {$maxId};\n";
