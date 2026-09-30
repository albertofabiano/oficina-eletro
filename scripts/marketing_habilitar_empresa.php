<?php
/**
 * Liga `empresas.marketing_habilitado=1` pra uma lista de empresas, buscadas por nome
 * (nome_fantasia OU razao_social, case-insensitive, LIKE — mesmo padrão de busca por nome já
 * usado noutros scripts deste diretório, ex. seed_dados_demo.php).
 *
 * Uso:
 *   php scripts/marketing_habilitar_empresa.php "tvservice" "Eletroli" "Timetec"
 *   php scripts/marketing_habilitar_empresa.php "tvservice" "Eletroli" "Timetec" --aplicar
 *
 * Por padrão roda em modo SIMULAÇÃO (só mostra quem seria afetado). Sem nenhum termo
 * passado, cai nas 3 empresas do piloto do módulo Marketing (tvservice, Eletroli, Timetec).
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/Helpers/functions.php';
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

$aplicar = in_array('--aplicar', $argv, true);
$termos = array_values(array_filter(array_slice($argv, 1), fn($a) => $a !== '--aplicar'));
if (!$termos) $termos = ['tvservice', 'Eletroli', 'Timetec'];

$db = App\Core\DB::pdo();
echo ($aplicar ? "MODO APLICAR — vai gravar de verdade no banco.\n" : "MODO SIMULAÇÃO — nada será gravado (rode com --aplicar pra gravar de verdade).\n");
echo str_repeat('-', 78) . "\n";

// Sem LIMIT de propósito — um termo como "tvservice" pode casar uma rede inteira de
// unidades, e cortar silenciosamente depois de N resultados esconderia unidade real do
// pedido "habilite todas as X" sem nenhum aviso.
$stmt = $db->prepare(
    "SELECT id, nome_fantasia, razao_social, marketing_habilitado FROM empresas
     WHERE nome_fantasia LIKE ? OR razao_social LIKE ?"
);

$encontradas = [];
foreach ($termos as $termo) {
    $like = '%' . $termo . '%';
    $stmt->execute([$like, $like]);
    $rows = $stmt->fetchAll();
    if (!$rows) {
        echo "  \"{$termo}\": NENHUMA empresa encontrada.\n";
        continue;
    }
    foreach ($rows as $r) {
        $nome = $r['nome_fantasia'] ?: $r['razao_social'];
        $status = $r['marketing_habilitado'] ? 'já habilitado' : 'vai ser habilitado';
        echo "  \"{$termo}\" -> empresa #{$r['id']} \"{$nome}\" ({$status})\n";
        if (!$r['marketing_habilitado']) $encontradas[] = (int) $r['id'];
    }
}

echo str_repeat('-', 78) . "\n";
echo "Total a habilitar: " . count($encontradas) . "\n";

if (!$aplicar) {
    echo "\nRode com --aplicar pra gravar de verdade.\n";
    exit(0);
}

if ($encontradas) {
    $placeholders = implode(',', array_fill(0, count($encontradas), '?'));
    $upd = $db->prepare("UPDATE empresas SET marketing_habilitado = 1 WHERE id IN ({$placeholders})");
    $upd->execute($encontradas);
    echo "Habilitado pra " . count($encontradas) . " empresa(s).\n";
    echo "\nPra desfazer:\n  UPDATE empresas SET marketing_habilitado = 0 WHERE id IN (" . implode(',', $encontradas) . ");\n";
} else {
    echo "Nada a fazer.\n";
}
