<?php
/**
 * Preenche o slug das empresas que aparecem no diretório (listagem_publica=1)
 * mas ficaram sem slug — geralmente empresas que nunca salvaram a página
 * "Perfil Público no Diretório" (único lugar que gera o slug até hoje).
 * Sem slug, o card da empresa no diretório vira um link quebrado (volta pra listagem).
 *
 * Idempotente: só mexe em quem está com slug NULL ou vazio.
 * Uso: php tools/backfill_slugs.php
 */

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

use App\Core\DB;

$db = DB::pdo();

$mapaAcentos = ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
    'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','ó'=>'o','ò'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
    'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n',
    'Á'=>'a','À'=>'a','Ã'=>'a','Â'=>'a','Ä'=>'a','É'=>'e','È'=>'e','Ê'=>'e','Ë'=>'e',
    'Í'=>'i','Ì'=>'i','Î'=>'i','Ï'=>'i','Ó'=>'o','Ò'=>'o','Ô'=>'o','Õ'=>'o','Ö'=>'o',
    'Ú'=>'u','Ù'=>'u','Û'=>'u','Ü'=>'u','Ç'=>'c','Ñ'=>'n'];

$stmt = $db->query("SELECT id, nome_fantasia, cidade FROM empresas WHERE (slug IS NULL OR slug = '') AND nome_fantasia IS NOT NULL AND nome_fantasia != ''");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "Nenhuma empresa com nome preenchido e slug vazio. Nada a fazer.\n";
    exit;
}

$check  = $db->prepare("SELECT id FROM empresas WHERE slug = ? AND id != ?");
$update = $db->prepare("UPDATE empresas SET slug = ? WHERE id = ?");

foreach ($rows as $r) {
    $raw  = strtr($r['nome_fantasia'] . '-' . ($r['cidade'] ?? ''), $mapaAcentos);
    $raw  = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $raw));
    $slug = trim($raw, '-');
    if ($slug === '') { $slug = 'empresa-' . $r['id']; }

    $check->execute([$slug, $r['id']]);
    if ($check->fetch()) $slug .= '-' . $r['id'];

    $update->execute([$slug, $r['id']]);
    echo "Empresa #{$r['id']} ({$r['nome_fantasia']}) -> slug: {$slug}\n";
}

echo "\nPronto. " . count($rows) . " empresa(s) atualizada(s).\n";
