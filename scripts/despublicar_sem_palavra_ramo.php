<?php
/**
 * Despublica (listagem_publica=0) do diretório qualquer empresa NÃO reivindicada cujo nome
 * não bata em nenhuma palavra de empresa_palavras_servico() — mesma regra já aplicada em
 * DiretorioController::empresa() (noindex) e SitemapController::xml(). Complementa essas duas:
 * elas já escondiam essas fichas de buscadores, mas a ficha continuava visível na BUSCA INTERNA
 * do próprio site (/assistencias não filtra por palavra) — este script tira de lá também.
 *
 * Reversível de propósito: só muda listagem_publica pra 0, não apaga a linha. Reativar depois
 * (se a lista de palavras for ajustada) é só um UPDATE de volta pra 1 pelos ids afetados
 * (o resumo final imprime a lista de ids).
 *
 * Por padrão roda em modo SIMULAÇÃO (não grava nada, só mostra quantas/quais seriam afetadas).
 * Pra gravar:
 *   php scripts/despublicar_sem_palavra_ramo.php --aplicar
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
    "SELECT id, nome_fantasia, cidade, uf FROM empresas
      WHERE ativo = 1 AND listagem_publica = 1
        AND (reivindicada = 0 OR reivindicada IS NULL)
        AND slug IS NOT NULL AND slug <> ''
        AND COALESCE(nome_fantasia,'') <> ''
      ORDER BY id"
);

$idsParaDespublicar = [];
while ($e = $stmt->fetch()) {
    if (!empresa_nome_indica_servico($e['nome_fantasia'])) {
        $idsParaDespublicar[] = (int) $e['id'];
    }
}

$total = count($idsParaDespublicar);
echo "Empresas não reivindicadas SEM palavra do ramo (serão despublicadas): {$total}\n";

if (!$aplicar) {
    echo "\nAmostra dos primeiros 20 ids: " . implode(', ', array_slice($idsParaDespublicar, 0, 20)) . "\n";
    echo "\nRode com --aplicar pra gravar de verdade.\n";
    exit(0);
}

if ($total === 0) { echo "Nada a fazer.\n"; exit(0); }

// UPDATE em lotes de 1000 ids, evita uma cláusula IN gigante numa tacada só.
$lotes = array_chunk($idsParaDespublicar, 1000);
$afetadas = 0;
foreach ($lotes as $lote) {
    $placeholders = implode(',', array_fill(0, count($lote), '?'));
    $st = $db->prepare("UPDATE empresas SET listagem_publica = 0 WHERE id IN ($placeholders)");
    $st->execute($lote);
    $afetadas += $st->rowCount();
}

echo "\nDespublicadas: {$afetadas} de {$total}.\n";
echo "Pra reverter tudo (reativar essas mesmas empresas), guarde essa lista de ids ou rode:\n";
echo "  UPDATE empresas SET listagem_publica = 1 WHERE id IN (" . implode(',', $idsParaDespublicar) . ");\n";
