<?php
/**
 * Liga `exige_valor=1` no status nativo "Aprovado" (codigo='aprovado') de toda empresa já
 * cadastrada — achado real (OS 0684): nada impedia aprovar um orçamento com valor_total=0.
 * `LandingController::registrar()` já liga isso pra empresa NOVA a partir de agora; este
 * script cobre o passado, mesmo padrão já usado pelos outros backfills deste projeto
 * (ex.: tornar_nativo_status_aprovado.php).
 *
 * Não mexe em status CUSTOM com esse nome mas sem `codigo='aprovado'` (a empresa criou o
 * próprio "Aprovado" na mão e nunca rodou tornar_nativo_status_aprovado.php) — ligar um
 * comportamento de bloqueio sozinho, sem a empresa ter pedido, seria arriscado demais pra um
 * status que ela mesma configurou; só mexe no que já é reconhecidamente o nativo do sistema.
 *
 * Por padrão roda em modo SIMULAÇÃO (não grava nada, só conta e mostra amostra). Pra gravar:
 *   php scripts/habilitar_exige_valor_aprovado.php --aplicar
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/Helpers/functions.php';
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

$aplicar = in_array('--aplicar', $argv, true);
$db = App\Core\DB::pdo();

echo ($aplicar ? "MODO APLICAR — vai gravar de verdade no banco.\n" : "MODO SIMULAÇÃO — nada será gravado (rode com --aplicar pra gravar de verdade).\n");
echo str_repeat('-', 78) . "\n";

$stmt = $db->query(
    "SELECT id, empresa_id, nome FROM os_status WHERE codigo = 'aprovado' AND exige_valor = 0 ORDER BY id"
);
$pendentes = $stmt->fetchAll();

echo "Status nativo 'Aprovado' sem exige_valor ligado: " . count($pendentes) . "\n";

if (!$pendentes) { echo "Nada a fazer.\n"; exit(0); }

if (!$aplicar) {
    echo "Amostra dos primeiros 10:\n";
    foreach (array_slice($pendentes, 0, 10) as $p) echo "  - empresa #{$p['empresa_id']}: \"{$p['nome']}\" (id {$p['id']})\n";
    echo "\nRode com --aplicar pra gravar de verdade.\n";
    exit(0);
}

$stmtUpd = $db->prepare("UPDATE os_status SET exige_valor = 1 WHERE id = ?");
$idsAtualizados = [];
foreach ($pendentes as $p) {
    try {
        $stmtUpd->execute([$p['id']]);
        $idsAtualizados[] = $p['id'];
    } catch (\Throwable $e) {
        echo "  FALHA ao atualizar id {$p['id']} (empresa #{$p['empresa_id']}): " . $e->getMessage() . "\n";
    }
}

echo "Atualizados: " . count($idsAtualizados) . "\n";
if ($idsAtualizados) {
    echo "\nPra desfazer:\n";
    echo "  UPDATE os_status SET exige_valor = 0 WHERE id IN (" . implode(',', $idsAtualizados) . ");\n";
}
