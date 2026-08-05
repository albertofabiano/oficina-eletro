<?php
// Diagnóstico temporário: reproduz exatamente a lógica de acompanhar() pra achar
// por que $fotosEntrada vem vazio mesmo com linhas em os_fotos.
// Rodar de dentro de /var/www/fixaos: php debug_fotos.php

define('BASE_PATH', __DIR__);
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});
require BASE_PATH . '/app/Helpers/functions.php';

use App\Core\DB;

$token = '59e48292af793c52463b871449e07eb2';
$db = DB::pdo();

$stmt = $db->prepare("
    SELECT os.*,
           c.nome AS cliente_nome, c.telefone AS cliente_tel, c.whatsapp AS cliente_whats,
           e.tipo AS equip_tipo, e.marca AS equip_marca, e.modelo AS equip_modelo,
           e.numero_serie, e.cor AS equip_cor, e.estado_entrada,
           s.nome AS status_nome, s.cor AS status_cor, s.tipo AS status_tipo,
           t.nome AS tecnico_nome,
           emp.nome_fantasia AS empresa_nome, emp.telefone AS empresa_tel,
           emp.whatsapp AS empresa_whats, emp.logo AS empresa_logo,
           emp.slug AS empresa_slug, emp.listagem_publica AS empresa_listada,
           emp.logradouro, emp.numero AS emp_numero, emp.bairro, emp.cidade, emp.uf
    FROM ordens_servico os
    JOIN clientes c ON c.id = os.cliente_id
    JOIN equipamentos e ON e.id = os.equipamento_id
    JOIN os_status s ON s.id = os.status_id
    JOIN empresas emp ON emp.id = os.empresa_id
    LEFT JOIN usuarios t ON t.id = os.tecnico_id
    WHERE os.token_publico = ?
");
$stmt->execute([$token]);
$os = $stmt->fetch();

if (!$os) {
    echo "OS não encontrada pela query principal — PARA AQUI, esse é o bug.\n";
    exit;
}

echo "OS encontrada. id=" . var_export($os['id'], true) . " (tipo: " . gettype($os['id']) . ")\n";
echo "empresa_id=" . var_export($os['empresa_id'], true) . " (tipo: " . gettype($os['empresa_id']) . ")\n";
echo "numero=" . var_export($os['numero'], true) . "\n";

$fts = $db->prepare("SELECT arquivo FROM os_fotos WHERE os_id = ? AND empresa_id = ? ORDER BY id ASC");
$fts->execute([$os['id'], $os['empresa_id']]);
$fotosEntrada = $fts->fetchAll();

echo "Fotos encontradas: " . count($fotosEntrada) . "\n";
print_r($fotosEntrada);

// Confere se a classe/método realmente tem esse código (garante que não é outro arquivo sendo carregado)
$refl = new ReflectionMethod(\App\Controllers\OrdemServicoController::class, 'acompanhar');
echo "\nArquivo real de OrdemServicoController::acompanhar(): " . $refl->getFileName() . "\n";
echo "Linha de início do método: " . $refl->getStartLine() . "\n";
