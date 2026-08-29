<?php
/**
 * Relatório read-only: confere quantas das empresas do diretório (ativo=1, listagem_publica=1)
 * estão indexáveis hoje, usando a MESMA regra de DiretorioController::empresa() ($noindex) e
 * SitemapController::xml() — reaproveita empresa_nome_indica_servico() em vez de reimplementar
 * o critério, pra não haver risco de divergência entre este relatório e o comportamento real.
 *
 * Regra: indexável = tem nome_fantasia preenchido E (reivindicada=1 OU nome bate palavra do
 * ramo). Não indexável = sem nome, OU (não reivindicada E nome não bate nenhuma palavra do ramo).
 *
 * Não grava nada — só lê e imprime. Rodar:
 *   php scripts/verificar_indexacao_diretorio.php
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/Helpers/functions.php';
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

$db = App\Core\DB::pdo();

$stmt = $db->query(
    "SELECT id, nome_fantasia, reivindicada, slug
     FROM empresas
     WHERE ativo = 1 AND listagem_publica = 1"
);

$total = 0;
$semNome = 0;
$semSlug = 0;
$reivindicadaComNome = 0;
$naoReivindicadaComPalavra = 0;
$naoReivindicadaSemPalavra = 0;
$amostraNoindexSemPalavra = [];

while ($e = $stmt->fetch()) {
    $total++;
    $temNome = trim((string) ($e['nome_fantasia'] ?? '')) !== '';
    $temSlug = trim((string) ($e['slug'] ?? '')) !== '';
    if (!$temSlug) $semSlug++;
    if (!$temNome) { $semNome++; continue; }

    if (!empty($e['reivindicada'])) {
        $reivindicadaComNome++;
        continue;
    }

    if (empresa_nome_indica_servico($e['nome_fantasia'])) {
        $naoReivindicadaComPalavra++;
    } else {
        $naoReivindicadaSemPalavra++;
        if (count($amostraNoindexSemPalavra) < 15) $amostraNoindexSemPalavra[] = $e['nome_fantasia'];
    }
}

$indexaveis = $reivindicadaComNome + $naoReivindicadaComPalavra;
$naoIndexaveis = $semNome + $naoReivindicadaSemPalavra;

echo str_repeat('-', 78) . "\n";
echo "Total de empresas no diretório (ativo=1, listagem_publica=1): {$total}\n";
echo "  Sem slug (não geram URL válida de qualquer forma): {$semSlug}\n";
echo str_repeat('-', 78) . "\n";
echo "INDEXÁVEIS (aparecem no sitemap.xml e sem noindex na página): {$indexaveis}\n";
echo "  - Reivindicadas com nome: {$reivindicadaComNome}\n";
echo "  - Não reivindicadas, nome bate palavra do ramo: {$naoReivindicadaComPalavra}\n";
echo str_repeat('-', 78) . "\n";
echo "NÃO INDEXÁVEIS (noindex, fora do sitemap): {$naoIndexaveis}\n";
echo "  - Sem nome_fantasia preenchido: {$semNome}\n";
echo "  - Não reivindicadas, nome NÃO bate nenhuma palavra do ramo: {$naoReivindicadaSemPalavra}\n";
echo str_repeat('-', 78) . "\n";

if ($amostraNoindexSemPalavra) {
    echo "Amostra de nomes fora do índice por não bater palavra do ramo:\n";
    foreach ($amostraNoindexSemPalavra as $n) echo "  - {$n}\n";
    echo str_repeat('-', 78) . "\n";
}

echo "Pra conferir contra o sitemap real publicado:\n";
echo "  curl -s https://fixaos.com.br/sitemap.xml | grep -c '<loc>.*assistencias/[^/]*</loc>'\n";
echo "Esse número deve bater com \"INDEXÁVEIS\" acima (± páginas de cidade, que usam outra contagem).\n";
