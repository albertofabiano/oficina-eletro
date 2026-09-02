<?php
/**
 * Reduz a lista de "OS aguardando pagamento" (painel do Fluxo de Caixa) de uma empresa pra
 * só as N mais recentes, marcando o resto como pago — feito pra limpar o excesso de OS
 * fictícias geradas por scripts/seed_dados_demo.php antes de gravar vídeos institucionais
 * (118 linhas nesse painel não cabem bem numa tela de demonstração).
 *
 * Usa exatamente o mesmo critério de Financeiro::osPendentes() (status tipo concluida
 * "Pronto" — nunca 'entregue'/"Fechado", que já foi retirado —, situacao_pagamento
 * pendente/parcial, valor_total > 0, não fechada_sem_receita),
 * ordenado por data_conclusao DESC — as N primeiras dessa ordem são as "mais recentes" e
 * ficam como estão; o resto vira situacao_pagamento='pago', valor_pago=valor_total.
 *
 * Só mexe em `ordens_servico` (não gera fin_lancamentos) — mesmo escopo deliberadamente
 * restrito do seed original, ver CLAUDE.md "Seed de dados fictícios pra vídeos institucionais".
 *
 * Por padrão roda em modo SIMULAÇÃO (não grava nada). Pra gravar:
 *   php scripts/limpar_os_aguardando_pagamento.php --empresa=ID --aplicar
 *
 * Opções:
 *   --empresa=ID   obrigatório
 *   --manter=N     quantas ficam aguardando pagamento (padrão 10)
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
$manter     = max(0, (int) $argOpt('manter', 10));

if ($empresaArg === null) {
    fwrite(STDERR, "Uso: php scripts/limpar_os_aguardando_pagamento.php --empresa=ID [--manter=10] [--aplicar]\n");
    exit(1);
}

$db  = App\Core\DB::pdo();
$eid = (int) $empresaArg;

$stmt = $db->prepare("SELECT id, nome_fantasia, razao_social FROM empresas WHERE id = ?");
$stmt->execute([$eid]);
$empresa = $stmt->fetch();
if (!$empresa) { fwrite(STDERR, "Empresa #{$eid} não encontrada.\n"); exit(1); }

echo ($aplicar ? "MODO APLICAR — vai gravar de verdade no banco.\n" : "MODO SIMULAÇÃO — nada será gravado (rode com --aplicar pra gravar de verdade).\n");
echo "Empresa: #{$eid} — {$empresa['nome_fantasia']} ({$empresa['razao_social']})\n";
echo str_repeat('-', 78) . "\n";

$stmt = $db->prepare(
    "SELECT os.id, os.numero, os.valor_total, os.data_conclusao, c.nome AS cliente_nome
     FROM ordens_servico os
     JOIN os_status s ON s.id = os.status_id
     JOIN clientes c ON c.id = os.cliente_id
     WHERE os.empresa_id = ? AND s.tipo = 'concluida'
       AND COALESCE(os.fechada_sem_receita, 0) = 0
       AND os.situacao_pagamento IN ('pendente','parcial')
       AND os.valor_total > 0
     ORDER BY os.data_conclusao DESC"
);
$stmt->execute([$eid]);
$todas = $stmt->fetchAll();

$total = count($todas);
echo "OS aguardando pagamento hoje: {$total}\n";

if ($total <= $manter) {
    echo "Já são {$total} (<= {$manter}) — nada a fazer.\n";
    exit;
}

$ficam    = array_slice($todas, 0, $manter);
$marcadas = array_slice($todas, $manter);

echo "\nFicam aguardando pagamento (" . count($ficam) . "):\n";
foreach ($ficam as $os) {
    echo "  - OS {$os['numero']} | {$os['cliente_nome']} | R$ " . number_format((float) $os['valor_total'], 2, ',', '.') . " | conclusão {$os['data_conclusao']}\n";
}

echo "\nVão ser marcadas como pagas (" . count($marcadas) . "):\n";
$somaMarcadas = array_sum(array_column($marcadas, 'valor_total'));
echo "  (primeiras 5 de exemplo)\n";
foreach (array_slice($marcadas, 0, 5) as $os) {
    echo "  - OS {$os['numero']} | {$os['cliente_nome']} | R$ " . number_format((float) $os['valor_total'], 2, ',', '.') . "\n";
}
echo "  ... e mais " . (count($marcadas) - min(5, count($marcadas))) . " OS. Soma: R$ " . number_format($somaMarcadas, 2, ',', '.') . "\n";

if (!$aplicar) {
    echo "\nRode com --aplicar pra gravar de verdade.\n";
    exit;
}

$db->beginTransaction();
try {
    $stmtUpdate = $db->prepare("UPDATE ordens_servico SET situacao_pagamento = 'pago', valor_pago = valor_total WHERE id = ?");
    foreach ($marcadas as $os) $stmtUpdate->execute([$os['id']]);
    $db->commit();
    echo "\nConcluído — " . count($marcadas) . " OS marcadas como pagas. Ficaram " . count($ficam) . " aguardando pagamento.\n";
} catch (Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, "Erro — nada foi gravado (rollback): " . $e->getMessage() . "\n");
    exit(1);
}
