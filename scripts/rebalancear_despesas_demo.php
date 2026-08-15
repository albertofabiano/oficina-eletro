<?php
/**
 * Corrige as despesas fictícias (numero_documento='SEED-DEMO', geradas por
 * scripts/seed_despesas_demo.php) de uma empresa demo, pra parar de aparecer saldo negativo
 * no Financeiro em recortes "mês até hoje" — bug real do gerador original: despesas fixas
 * (aluguel, salários, energia, água, internet, contabilidade, impostos) eram sempre lançadas
 * nos primeiros 10 dias do mês, então qualquer corte no meio do mês mostrava quase todo o
 * custo fixo já descontado enquanto a receita (que segue a data de conclusão de cada OS,
 * espalhada pelo mês inteiro) ainda não tinha acompanhado.
 *
 * Faz duas coisas em cada despesa SEED-DEMO:
 *   1) Se for uma das categorias fixas, sorteia um novo dia dentro do MESMO mês (em vez de só
 *      os primeiros 10 dias) — nunca no futuro, nunca depois de hoje se for o mês corrente.
 *   2) Multiplica o valor por --fator (padrão 0.65), abrindo uma margem confortável em vez de
 *      ficar raspando perto de zero em qualquer recorte.
 *
 * Só mexe em fin_lancamentos com numero_documento='SEED-DEMO' — nunca toca em lançamento real.
 *
 * Por padrão roda em modo SIMULAÇÃO (não grava nada). Pra gravar:
 *   php scripts/rebalancear_despesas_demo.php --empresa=ID --aplicar
 *
 * Opções:
 *   --empresa=ID   obrigatório
 *   --fator=0.65   multiplicador aplicado a cada valor de despesa (padrão 0.65)
 */

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});
require BASE_PATH . '/app/Helpers/functions.php';

$aplicar = in_array('--aplicar', $argv, true);

$argOpt = function (string $nome, $default) use ($argv) {
    foreach ($argv as $a) {
        if (str_starts_with($a, "--{$nome}=")) return substr($a, strlen($nome) + 3);
    }
    return $default;
};

$empresaArg = $argOpt('empresa', null);
$fator      = (float) $argOpt('fator', 0.65);

if ($empresaArg === null) {
    fwrite(STDERR, "Uso: php scripts/rebalancear_despesas_demo.php --empresa=ID [--fator=0.65] [--aplicar]\n");
    exit(1);
}

$db  = App\Core\DB::pdo();
$eid = (int) $empresaArg;

$stmt = $db->prepare("SELECT id, nome_fantasia, razao_social FROM empresas WHERE id = ?");
$stmt->execute([$eid]);
$empresa = $stmt->fetch();
if (!$empresa) { fwrite(STDERR, "Empresa #{$eid} não encontrada.\n"); exit(1); }

// Mesma lista de categorias fixas de seed_despesas_demo.php.
$FIXAS = [
    'Aluguel do ponto comercial', 'Energia elétrica', 'Água e esgoto', 'Internet e telefone',
    'Salários e encargos', 'Contabilidade (honorários)', 'Impostos (Simples Nacional)',
];

echo ($aplicar ? "MODO APLICAR — vai gravar de verdade no banco.\n" : "MODO SIMULAÇÃO — nada será gravado (rode com --aplicar pra gravar de verdade).\n");
echo "Empresa: #{$eid} — {$empresa['nome_fantasia']} ({$empresa['razao_social']})\n";
echo "Fator aplicado ao valor de cada despesa: {$fator}\n";
echo str_repeat('-', 78) . "\n";

$stmt = $db->prepare(
    "SELECT id, descricao, valor, data_vencimento, data_pagamento, status
     FROM fin_lancamentos
     WHERE empresa_id = ? AND tipo = 'despesa' AND numero_documento = 'SEED-DEMO'"
);
$stmt->execute([$eid]);
$linhas = $stmt->fetchAll();

if (!$linhas) {
    echo "Nenhuma despesa SEED-DEMO encontrada — rode scripts/seed_despesas_demo.php primeiro.\n";
    exit;
}

$hoje = new DateTime();
$somaAntes = 0.0;
$somaDepois = 0.0;
$atualizacoes = [];

foreach ($linhas as $l) {
    $somaAntes += (float) $l['valor'];
    $novoValor = round((float) $l['valor'] * $fator, 2);
    $somaDepois += $novoValor;

    $novaVencimento = $l['data_vencimento'];
    $novaPagamento  = $l['data_pagamento'];

    if (in_array($l['descricao'], $FIXAS, true)) {
        $dt = new DateTime($l['data_vencimento']);
        $ano = (int) $dt->format('Y');
        $mes = (int) $dt->format('n');
        $ultimoDia = (int) $dt->format('t');
        // Se for o mês corrente, não sorteia um dia no futuro.
        if ($ano === (int) $hoje->format('Y') && $mes === (int) $hoje->format('n')) {
            $ultimoDia = min($ultimoDia, (int) $hoje->format('j'));
        }
        $novoDia = mt_rand(1, max(1, $ultimoDia));
        $novaVencimento = sprintf('%04d-%02d-%02d', $ano, $mes, $novoDia);
        if ($l['status'] === 'pago') $novaPagamento = $novaVencimento;
    }

    $atualizacoes[] = [
        'id' => $l['id'], 'descricao' => $l['descricao'],
        'valor_antigo' => (float) $l['valor'], 'valor_novo' => $novoValor,
        'venc_antiga' => $l['data_vencimento'], 'venc_nova' => $novaVencimento,
        'pag_nova' => $novaPagamento,
    ];
}

echo "Despesas SEED-DEMO encontradas: " . count($linhas) . "\n";
echo "Soma antes: R$ " . number_format($somaAntes, 2, ',', '.') . "\n";
echo "Soma depois: R$ " . number_format($somaDepois, 2, ',', '.') . "\n";

// Prévia do "mês até hoje" — o recorte que estava aparecendo negativo.
$inicioMes = $hoje->format('Y-m-01');
$hojeStr   = $hoje->format('Y-m-d');

function somaPeriodo(PDO $db, int $eid, string $tipo, string $ini, string $fim, ?array $substituir = null): float
{
    $stmt = $db->prepare(
        "SELECT id, valor, data_pagamento FROM fin_lancamentos
         WHERE empresa_id = ? AND tipo = ? AND status = 'pago' AND data_pagamento BETWEEN ? AND ?"
    );
    $stmt->execute([$eid, $tipo, $ini, $fim]);
    $total = 0.0;
    foreach ($stmt->fetchAll() as $r) {
        if ($substituir && isset($substituir[$r['id']])) {
            $novo = $substituir[$r['id']];
            if ($novo['pag_nova'] === null || $novo['pag_nova'] < $ini || $novo['pag_nova'] > $fim) continue;
            $total += $novo['valor_novo'];
        } else {
            $total += (float) $r['valor'];
        }
    }
    return $total;
}

$substituirPorId = [];
foreach ($atualizacoes as $u) $substituirPorId[$u['id']] = $u;

$receitaMes = somaPeriodo($db, $eid, 'receita', $inicioMes, $hojeStr);
$despesaAntes = somaPeriodo($db, $eid, 'despesa', $inicioMes, $hojeStr);
$despesaDepois = somaPeriodo($db, $eid, 'despesa', $inicioMes, $hojeStr, $substituirPorId);

echo str_repeat('-', 78) . "\n";
echo "Recorte '{$inicioMes} a {$hojeStr}' (o card do Financeiro):\n";
echo "  Receita paga: R$ " . number_format($receitaMes, 2, ',', '.') . "\n";
echo "  Despesa paga ANTES: R$ " . number_format($despesaAntes, 2, ',', '.') . " → saldo " . number_format($receitaMes - $despesaAntes, 2, ',', '.') . "\n";
echo "  Despesa paga DEPOIS: R$ " . number_format($despesaDepois, 2, ',', '.') . " → saldo " . number_format($receitaMes - $despesaDepois, 2, ',', '.') . "\n";
echo str_repeat('-', 78) . "\n";

if (!$aplicar) {
    echo "Rode com --aplicar pra gravar de verdade.\n";
    exit;
}

$db->beginTransaction();
try {
    $stmtUp = $db->prepare(
        "UPDATE fin_lancamentos SET valor = ?, data_vencimento = ?, data_pagamento = ? WHERE id = ?"
    );
    foreach ($atualizacoes as $u) {
        $stmtUp->execute([$u['valor_novo'], $u['venc_nova'], $u['pag_nova'], $u['id']]);
    }
    $db->commit();
    echo "Concluído — " . count($atualizacoes) . " despesas atualizadas.\n";
} catch (Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, "Erro — nada foi gravado (rollback): " . $e->getMessage() . "\n");
    exit(1);
}
