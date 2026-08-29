<?php
/**
 * Torna "Não Apresenta Defeito" nativo (bloqueado=1, codigo='sem_defeito') pra TODAS as
 * empresas já cadastradas — LandingController::registrar() já semeia esse status pra empresa
 * NOVA a partir de agora (ver CLAUDE.md "Status de OS: 'Fechar OS sem débito'..."), este script
 * cobre o passado, pedido explícito do usuário ("quero que seja nativo para todas, inclusive
 * para as que já estão cadastradas").
 *
 * Só mexe em empresa que JÁ USA o módulo de OS (tem pelo menos 1 linha em os_status) — empresa
 * só-diretório (importada de CNPJ, ou tipo_conta='diretorio' sem nunca ter passado pelo
 * onboarding completo) não tem OS nenhuma, criar status pra ela seria lixo sem uso.
 *
 * Pra cada empresa elegível:
 *   - Já tem um status com nome batendo "apresenta defeito"/"sem defeito" (ou já codigo=
 *     'sem_defeito', reentrância) → só marca esse MESMO registro como nativo
 *     (codigo='sem_defeito', bloqueado=1). Não mexe em cor/tipo/permite_fechar/sem_valor —
 *     preserva o que a empresa já configurou, só trava o registro como nativo.
 *   - Não tem nenhum → cria um novo, na definição canônica (mesma usada em
 *     LandingController::registrar()), ordem = MAX(ordem)+1 da empresa.
 *
 * Por padrão roda em modo SIMULAÇÃO (não grava nada, só conta e mostra amostra). Pra gravar:
 *   php scripts/tornar_nativo_status_sem_defeito.php --aplicar
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

// Empresas que já usam o módulo de OS (têm pelo menos 1 os_status).
$empresasComOs = $db->query("SELECT DISTINCT empresa_id FROM os_status")->fetchAll(PDO::FETCH_COLUMN);
echo "Empresas com módulo de OS (têm status cadastrado): " . count($empresasComOs) . "\n";

$stmtBusca = $db->prepare(
    "SELECT id, nome, codigo FROM os_status WHERE empresa_id = ?
     AND (codigo = 'sem_defeito' OR nome LIKE '%apresenta%defeito%' OR nome LIKE '%efeito%')
     ORDER BY id"
);
$stmtOrdem = $db->prepare("SELECT COALESCE(MAX(ordem),0) FROM os_status WHERE empresa_id = ?");

$jaNativo = 0; $paraMarcar = []; $paraCriar = [];
foreach ($empresasComOs as $eid) {
    $stmtBusca->execute([$eid]);
    $candidatos = $stmtBusca->fetchAll();

    $achado = null;
    foreach ($candidatos as $c) {
        $norm = remover_acentos(mb_strtolower($c['nome']));
        if ($c['codigo'] === 'sem_defeito' || str_contains($norm, 'apresenta defeito') || str_contains($norm, 'sem defeito')) {
            $achado = $c;
            break;
        }
    }

    if ($achado) {
        if ($achado['codigo'] === 'sem_defeito') { $jaNativo++; continue; } // já rodado antes
        $paraMarcar[] = ['id' => $achado['id'], 'empresa_id' => $eid, 'nome' => $achado['nome']];
    } else {
        $stmtOrdem->execute([$eid]);
        $novaOrdem = min(255, (int) $stmtOrdem->fetchColumn() + 1);
        $paraCriar[] = ['empresa_id' => $eid, 'ordem' => $novaOrdem];
    }
}

echo "Já nativo (rodou antes, nada a fazer): {$jaNativo}\n";
echo "Tem status custom com esse nome — será marcado como nativo: " . count($paraMarcar) . "\n";
echo "Não tem nenhum — será criado do zero: " . count($paraCriar) . "\n";
echo str_repeat('-', 78) . "\n";

if (!$aplicar) {
    echo "Amostra dos primeiros 10 a marcar como nativo:\n";
    foreach (array_slice($paraMarcar, 0, 10) as $m) echo "  - empresa #{$m['empresa_id']}: \"{$m['nome']}\" (id {$m['id']})\n";
    echo "\nAmostra das primeiras 10 empresas que vão ganhar o status novo:\n";
    foreach (array_slice($paraCriar, 0, 10) as $c) echo "  - empresa #{$c['empresa_id']}\n";
    echo "\nRode com --aplicar pra gravar de verdade.\n";
    exit(0);
}

$stmtUpd = $db->prepare("UPDATE os_status SET codigo = 'sem_defeito', bloqueado = 1 WHERE id = ?");
$idsMarcados = [];
foreach ($paraMarcar as $m) {
    try {
        $stmtUpd->execute([$m['id']]);
        $idsMarcados[] = $m['id'];
    } catch (\Throwable $e) {
        echo "  FALHA ao marcar id {$m['id']} (empresa #{$m['empresa_id']}): " . $e->getMessage() . "\n";
    }
}

$stmtIns = $db->prepare(
    "INSERT INTO os_status (empresa_id, codigo, nome, cor, cor_fonte, ordem, tipo, permite_fechar, sem_valor, bloqueado)
     VALUES (?, 'sem_defeito', 'Não Apresenta Defeito', '#42c266', '#ffffff', ?, 'concluida', 1, 1, 1)"
);
$idsCriados = [];
foreach ($paraCriar as $c) {
    try {
        $stmtIns->execute([$c['empresa_id'], $c['ordem']]);
        $idsCriados[] = (int) $db->lastInsertId();
    } catch (\Throwable $e) {
        echo "  FALHA ao criar pra empresa #{$c['empresa_id']}: " . $e->getMessage() . "\n";
    }
}

echo "Marcados como nativo (registro já existia): " . count($idsMarcados) . "\n";
echo "Criados do zero: " . count($idsCriados) . "\n";

echo "\nPra desfazer:\n";
if ($idsMarcados) {
    echo "  UPDATE os_status SET codigo = NULL, bloqueado = 0 WHERE id IN (" . implode(',', $idsMarcados) . ");\n";
}
if ($idsCriados) {
    echo "  DELETE FROM os_status WHERE id IN (" . implode(',', $idsCriados) . ");\n";
}
