<?php
/**
 * Popula despesas fictícias no Financeiro de uma empresa (fin_lancamentos tipo='despesa'),
 * espalhadas nos últimos ~13 meses — complemento de scripts/seed_dados_demo.php, que
 * deliberadamente não mexe no Financeiro. Feito pra dashboards/relatórios financeiros não
 * aparecerem com "Despesas pagas: R$ 0,00" em vídeos institucionais.
 *
 * Gera despesas fixas mensais (aluguel, energia, água, internet, salários, contabilidade,
 * impostos) + despesas variáveis por mês (compra de peças/estoque, manutenção, marketing,
 * combustível), em quantidade e valor plausíveis pra uma assistência técnica pequena.
 *
 * Marca cada lançamento com numero_documento='SEED-DEMO' (campo não usado em nenhum outro
 * lugar do sistema) só pra facilitar identificar/apagar depois:
 *   DELETE FROM fin_lancamentos WHERE empresa_id=? AND numero_documento='SEED-DEMO';
 *
 * Por padrão roda em modo SIMULAÇÃO (não grava nada). Pra gravar:
 *   php scripts/seed_despesas_demo.php --empresa=ID --aplicar
 *
 * Opções:
 *   --empresa=ID   obrigatório
 *   --meses=N      quantos meses pra trás gerar (padrão 13)
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
$meses      = max(1, (int) $argOpt('meses', 13));

if ($empresaArg === null) {
    fwrite(STDERR, "Uso: php scripts/seed_despesas_demo.php --empresa=ID [--meses=13] [--aplicar]\n");
    exit(1);
}

$db  = App\Core\DB::pdo();
$eid = (int) $empresaArg;

$stmt = $db->prepare("SELECT id, nome_fantasia, razao_social FROM empresas WHERE id = ?");
$stmt->execute([$eid]);
$empresa = $stmt->fetch();
if (!$empresa) { fwrite(STDERR, "Empresa #{$eid} não encontrada.\n"); exit(1); }

$stmt = $db->prepare("SELECT id FROM fin_contas WHERE empresa_id = ? AND ativo = 1 ORDER BY (tipo = 'caixa') DESC, id ASC LIMIT 1");
$stmt->execute([$eid]);
$contaId = $stmt->fetchColumn();
if (!$contaId) { fwrite(STDERR, "Empresa não tem nenhuma fin_contas cadastrada — impossível lançar despesa.\n"); exit(1); }

echo ($aplicar ? "MODO APLICAR — vai gravar de verdade no banco.\n" : "MODO SIMULAÇÃO — nada será gravado (rode com --aplicar pra gravar de verdade).\n");
echo "Empresa: #{$eid} — {$empresa['nome_fantasia']} ({$empresa['razao_social']})\n";
echo "Conta usada nos lançamentos: #{$contaId}\n";
echo str_repeat('-', 78) . "\n";

function precoEm(float $min, float $max): float
{
    return round(mt_rand((int) ($min * 100), (int) ($max * 100)) / 100, 2);
}

// [nome, min, max] — uma vez por mês, sempre.
$FIXAS = [
    ['Aluguel do ponto comercial', 1200, 1900],
    ['Energia elétrica', 350, 700],
    ['Água e esgoto', 80, 150],
    ['Internet e telefone', 150, 250],
    ['Salários e encargos', 6500, 12000],
    ['Contabilidade (honorários)', 300, 500],
    ['Impostos (Simples Nacional)', 400, 1600],
];

// [nome, min, max, qtdMin, qtdMax] — quantidade variável por mês.
$VARIAVEIS = [
    ['Compra de peças e componentes (fornecedor)', 150, 1800, 3, 8],
    ['Manutenção de equipamentos e ferramentas', 100, 800, 0, 2],
    ['Marketing e anúncios online', 100, 600, 0, 2],
    ['Combustível e transporte', 80, 350, 1, 4],
    ['Material de escritório e limpeza', 50, 220, 0, 2],
];

$FORMAS = ['pix','boleto','transferencia','dinheiro','cartao_debito'];

// Categorias de despesa — reaproveita se já existir com o mesmo nome.
$stmt = $db->prepare("SELECT id, nome FROM fin_categorias WHERE empresa_id = ? AND tipo = 'despesa'");
$stmt->execute([$eid]);
$catId = [];
foreach ($stmt->fetchAll() as $r) $catId[$r['nome']] = (int) $r['id'];

$todosNomes = array_merge(array_column($FIXAS, 0), array_column($VARIAVEIS, 0));
$stmtCat = $db->prepare("INSERT INTO fin_categorias (empresa_id, tipo, nome, cor) VALUES (?, 'despesa', ?, '#dc3545')");

// Monta a lista de lançamentos em memória primeiro (pra poder mostrar prévia sem gravar)
$hoje = new DateTime();
$lancamentos = [];
for ($m = $meses - 1; $m >= 0; $m--) {
    $refMes = (clone $hoje)->modify("-{$m} month");
    $ano = (int) $refMes->format('Y');
    $mes = (int) $refMes->format('n');
    $ultimoDia = (int) $refMes->format('t');

    foreach ($FIXAS as [$nome, $min, $max]) {
        // Espalhada pelo mês inteiro (não só nos primeiros dias) — senão qualquer recorte
        // "mês até hoje" no Financeiro mostra quase todo o custo fixo já descontado enquanto a
        // receita (que segue a data de conclusão de cada OS, espalhada pelo mês todo) ainda não
        // acompanhou, e o saldo do período aparece artificialmente negativo até o mês fechar.
        $dia = mt_rand(1, $ultimoDia);
        $data = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
        if (strtotime($data) > time()) continue; // não lança despesa no futuro
        $lancamentos[] = [$nome, precoEm($min, $max), $data];
    }
    foreach ($VARIAVEIS as [$nome, $min, $max, $qtdMin, $qtdMax]) {
        $qtd = mt_rand($qtdMin, $qtdMax);
        for ($i = 0; $i < $qtd; $i++) {
            $dia = mt_rand(1, $ultimoDia);
            $data = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
            if (strtotime($data) > time()) continue;
            $lancamentos[] = [$nome, precoEm($min, $max), $data];
        }
    }
}

usort($lancamentos, fn($a, $b) => strcmp($a[2], $b[2]));

$total = array_sum(array_column($lancamentos, 1));
echo "Meses cobertos: {$meses}\n";
echo "Despesas a lançar: " . count($lancamentos) . "\n";
echo "Soma total: R$ " . number_format($total, 2, ',', '.') . " (média R$ " . number_format($total / $meses, 2, ',', '.') . "/mês)\n";
echo "\nExemplo (5 primeiras):\n";
foreach (array_slice($lancamentos, 0, 5) as [$nome, $valor, $data]) {
    echo "  - {$data} | {$nome} | R$ " . number_format($valor, 2, ',', '.') . "\n";
}

if (!$aplicar) {
    echo "\nRode com --aplicar pra gravar de verdade.\n";
    exit;
}

$db->beginTransaction();
try {
    foreach ($todosNomes as $nome) {
        if (!isset($catId[$nome])) {
            $stmtCat->execute([$eid, $nome]);
            $catId[$nome] = (int) $db->lastInsertId();
        }
    }

    $stmtIns = $db->prepare(
        "INSERT INTO fin_lancamentos
         (empresa_id, conta_id, categoria_id, tipo, descricao, valor, data_vencimento, data_pagamento, status, forma_pagamento, numero_documento)
         VALUES (?, ?, ?, 'despesa', ?, ?, ?, ?, ?, ?, 'SEED-DEMO')"
    );

    $limiteRecente = (new DateTime('-5 days'))->format('Y-m-d');
    foreach ($lancamentos as [$nome, $valor, $data]) {
        // As despesas dos últimos 5 dias ficam com uma chance de ainda estar pendente —
        // parece mais real do que tudo já pago até hoje.
        $pendente = $data >= $limiteRecente && mt_rand(1, 100) <= 40;
        $status = $pendente ? 'pendente' : 'pago';
        $dataPagamento = $pendente ? null : $data;
        $forma = $FORMAS[array_rand($FORMAS)];

        $stmtIns->execute([$eid, $contaId, $catId[$nome], $nome, $valor, $data, $dataPagamento, $status, $forma]);
    }

    $db->commit();
    echo "\nConcluído — " . count($lancamentos) . " despesas lançadas (R$ " . number_format($total, 2, ',', '.') . ").\n";
} catch (Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, "Erro — nada foi gravado (rollback): " . $e->getMessage() . "\n");
    exit(1);
}
