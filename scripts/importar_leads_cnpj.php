<?php
/**
 * Importa (upsert por CNPJ) um CSV já filtrado pra tabela leads_prospeccao,
 * usada pelo painel /master/prospeccao.
 *
 * O CSV esperado tem cabeçalho e estas colunas, nessa ordem:
 *   cnpj,razao_social,nome_fantasia,telefone,email,cnae,municipio,uf,situacao_cadastral
 * (é exatamente o que scripts/filtrar_rfb_estabelecimentos.php gera).
 *
 * USO:
 *   php scripts/importar_leads_cnpj.php /caminho/leads_filtrados.csv
 *
 * Rode a partir da raiz do projeto (precisa do autoload/bootstrap da app).
 */

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

use App\Core\DB;

$csv = $argv[1] ?? null;
if (!$csv || !is_file($csv)) {
    fwrite(STDERR, "Uso: php scripts/importar_leads_cnpj.php /caminho/leads_filtrados.csv\n");
    exit(1);
}

$db = DB::pdo();
$stmt = $db->prepare("
    INSERT INTO leads_prospeccao (cnpj, razao_social, nome_fantasia, telefone, email, cnae, municipio, uf, situacao_cadastral)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
      razao_social = VALUES(razao_social),
      nome_fantasia = VALUES(nome_fantasia),
      telefone = VALUES(telefone),
      email = VALUES(email),
      cnae = VALUES(cnae),
      municipio = VALUES(municipio),
      uf = VALUES(uf),
      situacao_cadastral = VALUES(situacao_cadastral)
");

$fh = fopen($csv, 'r');
$cabecalho = fgetcsv($fh); // descarta o cabeçalho
$inseridos = 0;
$erros = 0;

$db->beginTransaction();
while (($row = fgetcsv($fh)) !== false) {
    [$cnpj, $razao, $fantasia, $telefone, $email, $cnae, $municipio, $uf, $situacao] = array_pad($row, 9, null);
    $cnpj = preg_replace('/\D/', '', (string) $cnpj);
    if (strlen($cnpj) !== 14 || !$razao) { $erros++; continue; }

    try {
        $stmt->execute([$cnpj, $razao, $fantasia ?: null, $telefone ?: null, $email ?: null, $cnae, $municipio ?: null, $uf ?: null, $situacao ?: null]);
        $inseridos++;
    } catch (\Throwable $e) {
        $erros++;
        fwrite(STDERR, "Erro no CNPJ $cnpj: " . $e->getMessage() . "\n");
    }
}
$db->commit();
fclose($fh);

fwrite(STDOUT, "Concluído: $inseridos leads importados/atualizados, $erros linhas ignoradas.\n");
