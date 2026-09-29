<?php
/**
 * Apaga anúncios fictícios do Marketplace de Peças gerados por
 * `tools/demo_marketplace.php` (já removido, ver CLAUDE.md "Fim da conta de
 * demonstração") pra empresas cujo nome bate com o padrão da conta demo
 * (`nome_fantasia`/`razao_social` contendo "demo") — ex.: "Assistencia
 * Modelo (Demo)".
 *
 * Não depende de achar o usuário `demo@fixaos.com.br` (diferente de
 * scripts/remover_empresa_demo.php) — busca a empresa direto pelo NOME, já
 * que o anúncio pode sobreviver no Marketplace mesmo se o usuário/empresa
 * demo tiver sido removido por outro caminho antes (o vendedor de um
 * anúncio é sempre a linha em `empresas`, com FK `ON DELETE CASCADE` —
 * se o anúncio ainda aparece, a empresa vendedora ainda existe).
 *
 * Só apaga a LINHA do anúncio (`marketplace_anuncios`) — não mexe na
 * empresa em si nem no usuário dela; se a intenção for remover a empresa
 * demo por completo, use scripts/remover_empresa_demo.php também.
 *
 * Uso:
 *   php scripts/apagar_anuncios_fake_marketplace.php              # simulação
 *   php scripts/apagar_anuncios_fake_marketplace.php --aplicar     # apaga de verdade
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/Helpers/functions.php';
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

$aplicar = in_array('--aplicar', $argv, true);
$db = App\Core\DB::pdo();

echo ($aplicar ? "MODO APLICAR — vai apagar de verdade.\n" : "MODO SIMULAÇÃO — nada será apagado (rode com --aplicar pra apagar de verdade).\n");
echo str_repeat('-', 78) . "\n";

$stmt = $db->query("
    SELECT a.id, a.titulo, a.valor, a.imagem_principal, a.empresa_id_vendedor,
           e.nome_fantasia, e.razao_social
    FROM marketplace_anuncios a
    JOIN empresas e ON e.id = a.empresa_id_vendedor
    WHERE LOWER(e.nome_fantasia) LIKE '%demo%' OR LOWER(e.razao_social) LIKE '%demo%'
    ORDER BY e.id, a.id
");
$anuncios = $stmt->fetchAll();

if (!$anuncios) {
    echo "Nenhum anúncio de empresa com \"demo\" no nome encontrado — nada a fazer.\n";
    exit(0);
}

$porEmpresa = [];
foreach ($anuncios as $a) {
    $porEmpresa[$a['empresa_id_vendedor']]['nome'] = $a['nome_fantasia'] ?: $a['razao_social'];
    $porEmpresa[$a['empresa_id_vendedor']]['itens'][] = $a;
}

foreach ($porEmpresa as $eid => $grupo) {
    echo "Empresa #{$eid} \"{$grupo['nome']}\" — " . count($grupo['itens']) . " anúncio(s):\n";
    foreach ($grupo['itens'] as $a) {
        echo "  #{$a['id']}  R$ " . number_format((float) $a['valor'], 2, ',', '.') . "  {$a['titulo']}\n";
    }
}

echo str_repeat('-', 78) . "\n";
echo "Total: " . count($anuncios) . " anúncio(s) fictício(s).\n";

if (!$aplicar) {
    echo "\nRode com --aplicar pra apagar todos os anúncios acima.\n";
    exit(0);
}

$ids = array_column($anuncios, 'id');
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$db->prepare("DELETE FROM marketplace_anuncios WHERE id IN ({$placeholders})")->execute($ids);
echo count($ids) . " anúncio(s) apagado(s) (marketplace_historico_creditos.anuncio_id fica NULL sozinho, ON DELETE SET NULL).\n";

$uploadsDir = BASE_PATH . '/storage/uploads/marketplace/';
$apagadas = 0;
foreach ($anuncios as $a) {
    $arquivo = $a['imagem_principal'] ?? '';
    if ($arquivo === '' || !str_starts_with($arquivo, 'demo-')) continue; // só remove imagem gerada pelo script demo, nunca upload real
    $caminho = $uploadsDir . basename($arquivo);
    if (is_file($caminho) && unlink($caminho)) $apagadas++;
}
if ($apagadas > 0) echo "{$apagadas} imagem(ns) fictícia(s) (prefixo \"demo-\") removida(s) de storage/uploads/marketplace/.\n";
