<?php
/**
 * Importa empresas de leads_prospeccao (base nacional de CNPJ, 259 mil leads, já filtrada por
 * CNAE/situação ativa na importação original — ver filtrar_rfb_estabelecimentos.php) pra dentro
 * de `empresas`, como ficha NÃO reivindicada do Diretório — mesmo padrão de dado das ~17 mil já
 * existentes, só que cobrindo cidades que hoje não têm nenhuma empresa cadastrada.
 *
 * Escopo do que entra:
 *   - CNPJ que ainda NÃO existe em `empresas` (evita duplicar quem já foi importado antes).
 *   - Município que NÃO é a capital do estado (pedido do usuário — capitais já têm dado
 *     suficiente; ver $CAPITAIS abaixo, comparação sem acento/maiúscula).
 *   - Nome (nome_fantasia ou razao_social) bate em empresa_nome_indica_servico() — MESMA
 *     função usada pro noindex/sitemap/despublicar, não duplica a regra (app/Helpers/functions.php).
 *
 * Cada empresa entra com: reivindicada=0, listagem_publica=1, tipo_conta='diretorio',
 * plano='basico', ativo=1, slug calculado via slug_empresa_unico() — mesmo padrão das
 * empresas já existentes desse tipo (ver DiretorioController::cadastrarSalvar()).
 *
 * Por padrão roda em modo SIMULAÇÃO (não grava nada, só conta e mostra amostra). Pra gravar:
 *   php scripts/importar_leads_diretorio.php --aplicar
 * Opcional, pra testar num estado só primeiro:
 *   php scripts/importar_leads_diretorio.php --uf=BA --aplicar
 * Opcional, pra limitar quantidade (teste rápido):
 *   php scripts/importar_leads_diretorio.php --limite=100 --aplicar
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/Helpers/functions.php';
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

$aplicar = in_array('--aplicar', $argv, true);
$db = App\Core\DB::pdo();

$ufFiltro = null;
foreach ($argv as $arg) if (preg_match('/^--uf=([A-Za-z]{2})$/', $arg, $m)) $ufFiltro = strtoupper($m[1]);
$limite = null;
foreach ($argv as $arg) if (preg_match('/^--limite=(\d+)$/', $arg, $m)) $limite = (int) $m[1];

echo ($aplicar ? "MODO APLICAR — vai gravar de verdade no banco.\n" : "MODO SIMULAÇÃO — nada será gravado (rode com --aplicar pra gravar de verdade).\n");
if ($ufFiltro) echo "Filtrando só UF={$ufFiltro}.\n";
if ($limite) echo "Limite de {$limite} importações.\n";
echo str_repeat('-', 78) . "\n";

$CAPITAIS = [
    'AC' => 'rio branco', 'AL' => 'maceio', 'AP' => 'macapa', 'AM' => 'manaus',
    'BA' => 'salvador', 'CE' => 'fortaleza', 'DF' => 'brasilia', 'ES' => 'vitoria',
    'GO' => 'goiania', 'MA' => 'sao luis', 'MT' => 'cuiaba', 'MS' => 'campo grande',
    'MG' => 'belo horizonte', 'PA' => 'belem', 'PB' => 'joao pessoa', 'PR' => 'curitiba',
    'PE' => 'recife', 'PI' => 'teresina', 'RJ' => 'rio de janeiro', 'RN' => 'natal',
    'RS' => 'porto alegre', 'RO' => 'porto velho', 'RR' => 'boa vista',
    'SC' => 'florianopolis', 'SP' => 'sao paulo', 'SE' => 'aracaju', 'TO' => 'palmas',
];

// CNPJs já existentes em empresas — carregado uma vez, evita duplicar quem já foi importado.
$cnpjsExistentes = [];
$stmtCnpj = $db->query("SELECT cnpj FROM empresas WHERE cnpj IS NOT NULL AND cnpj <> ''");
while ($row = $stmtCnpj->fetch()) $cnpjsExistentes[$row['cnpj']] = true;
echo "CNPJs já cadastrados em empresas: " . count($cnpjsExistentes) . "\n";

$sql = "SELECT cnpj, razao_social, nome_fantasia, telefone, email, municipio, uf
        FROM leads_prospeccao
        WHERE municipio IS NOT NULL AND municipio <> '' AND uf IS NOT NULL AND uf <> ''";
$params = [];
if ($ufFiltro) { $sql .= " AND uf = ?"; $params[] = $ufFiltro; }
$stmt = $db->prepare($sql);
$stmt->execute($params);

$candidatos = [];
$totalLidos = 0; $puladosCapital = 0; $puladosDuplicado = 0; $puladosSemPalavra = 0;
while ($lead = $stmt->fetch()) {
    $totalLidos++;
    $cnpj = trim((string) $lead['cnpj']);
    if ($cnpj !== '' && isset($cnpjsExistentes[$cnpj])) { $puladosDuplicado++; continue; }

    $uf = strtoupper(trim((string) $lead['uf']));
    $municipioNorm = mb_strtolower(remover_acentos(trim((string) $lead['municipio'])));
    if (isset($CAPITAIS[$uf]) && $municipioNorm === $CAPITAIS[$uf]) { $puladosCapital++; continue; }

    $nome = trim((string) ($lead['nome_fantasia'] ?: $lead['razao_social']));
    if (!empresa_nome_indica_servico($nome)) { $puladosSemPalavra++; continue; }

    $candidatos[] = $lead;
    if ($limite && count($candidatos) >= $limite) break;
}

echo "Leads lidos: {$totalLidos}\n";
echo "Pulados (CNPJ já existe): {$puladosDuplicado}\n";
echo "Pulados (é a capital do estado): {$puladosCapital}\n";
echo "Pulados (nome sem palavra do ramo): {$puladosSemPalavra}\n";
echo "Candidatos a importar: " . count($candidatos) . "\n";
echo str_repeat('-', 78) . "\n";

if (!$aplicar) {
    echo "Amostra dos primeiros 20:\n";
    foreach (array_slice($candidatos, 0, 20) as $c) {
        $nome = trim((string) ($c['nome_fantasia'] ?: $c['razao_social']));
        echo "  - {$nome} — {$c['municipio']}/{$c['uf']}\n";
    }
    echo "\nRode com --aplicar pra gravar de verdade.\n";
    exit(0);
}

$stmtIns = $db->prepare(
    "INSERT INTO empresas
       (razao_social, nome_fantasia, cnpj, telefone, email, cidade, uf,
        tipo_conta, plano, reivindicada, listagem_publica, ativo)
     VALUES (?,?,?,?,?,?,?, 'diretorio', 'basico', 0, 1, 1)"
);

$importadas = 0; $falhas = 0; $idsImportados = [];
foreach ($candidatos as $c) {
    $nome = trim((string) ($c['nome_fantasia'] ?: $c['razao_social']));
    $cidade = trim((string) $c['municipio']);
    $uf = strtoupper(trim((string) $c['uf']));
    try {
        $db->beginTransaction();
        $stmtIns->execute([
            mb_substr(trim((string) $c['razao_social']), 0, 150) ?: mb_substr($nome, 0, 150),
            mb_substr($nome, 0, 150),
            trim((string) $c['cnpj']) ?: null,
            trim((string) $c['telefone']) ?: null,
            trim((string) $c['email']) ?: null,
            mb_substr($cidade, 0, 80),
            $uf ?: null,
        ]);
        $empresaId = (int) $db->lastInsertId();
        $slug = slug_empresa_unico($nome, $cidade, $empresaId, null);
        $db->prepare("UPDATE empresas SET slug = ? WHERE id = ?")->execute([$slug, $empresaId]);
        $db->commit();
        $importadas++;
        $idsImportados[] = $empresaId;
    } catch (\Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        $falhas++;
        echo "  FALHA ({$nome} — {$cidade}/{$uf}): " . $e->getMessage() . "\n";
    }
}

echo str_repeat('-', 78) . "\n";
echo "Importadas: {$importadas}\n";
echo "Falhas: {$falhas}\n";
if ($idsImportados) {
    echo "\nPra desfazer esta importação:\n";
    if (count($idsImportados) <= 200) {
        echo "  DELETE FROM empresas WHERE id IN (" . implode(',', $idsImportados) . ");\n";
    } else {
        // Lista grande — ids são sequenciais (nenhum outro INSERT concorrente esperado durante
        // o script), então a faixa min/max cobre exatamente o que foi criado aqui.
        echo "  DELETE FROM empresas WHERE id BETWEEN " . min($idsImportados) . " AND " . max($idsImportados) . ";\n";
        echo "  (" . count($idsImportados) . " ids, de " . min($idsImportados) . " até " . max($idsImportados) . ")\n";
    }
}
