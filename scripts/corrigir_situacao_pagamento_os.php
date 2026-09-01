<?php
/**
 * Corrige `situacao_pagamento` de OS onde o valor já pago (`valor_pago`) não bate mais com o
 * que a coluna diz ("pendente"/"parcial" mesmo já totalmente pago, ou vice-versa) — achado real
 * em produção (2026-09): OS 26554 (empresa 27) e OS 000022 (empresa 28115) apareciam pra sempre
 * em "OS aguardando pagamento" no Fluxo de Caixa mesmo já pagas (valor_pago >= valor_total).
 *
 * Causa raiz (corrigida em OrdemServicoController::adicionarServico()/adicionarPeca()/
 * removerServico()/removerPeca()): editar ou excluir um serviço/peça numa OS recalcula
 * `valor_total`, mas nunca recalculava `situacao_pagamento` — editar um item numa OS já
 * fechada e paga deixava a coluna desatualizada pra sempre. Este script corrige o dado já
 * gravado; o código já foi corrigido pra não acontecer de novo.
 *
 * Mesma fórmula usada em toda a aplicação (fechar()/adicionarAdiantamento()/etc.):
 *   valor_pago >= valor_total (e valor_total > 0) => 'pago'
 *   valor_pago > 0                                => 'parcial'
 *   senão                                          => 'pendente'
 * Só mexe em OS com `fechada_sem_receita = 0` (Sem Conserto/Recusado nunca deve virar "pago"
 * automaticamente aqui — é uma decisão de fora do sistema, não um recálculo automático).
 *
 * Roda em modo SIMULAÇÃO por padrão. Pra gravar:
 *   php scripts/corrigir_situacao_pagamento_os.php --aplicar
 *
 * Opções:
 *   --empresa=ID   restringe a uma empresa (padrão: todas)
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

$db = App\Core\DB::pdo();

echo ($aplicar ? "MODO APLICAR — vai gravar de verdade no banco.\n" : "MODO SIMULAÇÃO — nada será gravado (rode com --aplicar pra gravar de verdade).\n");
echo str_repeat('-', 90) . "\n";

$sql = "SELECT os.id, os.numero, os.empresa_id, os.valor_total, os.valor_pago, os.situacao_pagamento
        FROM ordens_servico os
        JOIN os_status s ON s.id = os.status_id
        WHERE s.tipo IN ('concluida','entregue')
          AND COALESCE(os.fechada_sem_receita, 0) = 0
          AND os.valor_total > 0";
$params = [];
if ($empresaArg !== null) {
    $sql .= " AND os.empresa_id = ?";
    $params[] = (int) $empresaArg;
}
$stmt = $db->prepare($sql);
$stmt->execute($params);
$todas = $stmt->fetchAll();

function situacaoCorreta(float $valorPago, float $valorTotal): string
{
    if ($valorPago <= 0) return 'pendente';
    return ($valorPago >= $valorTotal && $valorTotal > 0) ? 'pago' : 'parcial';
}

$divergentes = [];
foreach ($todas as $os) {
    $correta = situacaoCorreta((float) $os['valor_pago'], (float) $os['valor_total']);
    if ($correta !== $os['situacao_pagamento']) {
        $os['situacao_correta'] = $correta;
        $divergentes[] = $os;
    }
}

echo "OS analisadas: " . count($todas) . "\n";
echo "Divergências encontradas: " . count($divergentes) . "\n\n";

foreach ($divergentes as $os) {
    printf(
        "  OS %s (empresa #%d) — total R$ %s, pago R$ %s — situacao_pagamento: '%s' -> '%s'\n",
        $os['numero'], $os['empresa_id'],
        number_format((float) $os['valor_total'], 2, ',', '.'),
        number_format((float) $os['valor_pago'], 2, ',', '.'),
        $os['situacao_pagamento'], $os['situacao_correta']
    );
}

if (!$divergentes) {
    echo "Nada a corrigir.\n";
    exit;
}

if (!$aplicar) {
    echo "\nRode com --aplicar pra gravar de verdade.\n";
    exit;
}

$db->beginTransaction();
try {
    $stmtUpdate = $db->prepare("UPDATE ordens_servico SET situacao_pagamento = ? WHERE id = ?");
    foreach ($divergentes as $os) {
        $stmtUpdate->execute([$os['situacao_correta'], $os['id']]);
    }
    $db->commit();
    echo "\nConcluído — " . count($divergentes) . " OS corrigidas.\n";
    echo "\nPra desfazer, se precisar:\n";
    foreach ($divergentes as $os) {
        echo "UPDATE ordens_servico SET situacao_pagamento = '{$os['situacao_pagamento']}' WHERE id = {$os['id']};\n";
    }
} catch (Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, "Erro — nada foi gravado (rollback): " . $e->getMessage() . "\n");
    exit(1);
}
