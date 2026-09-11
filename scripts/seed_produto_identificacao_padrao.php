<?php
/**
 * Backfill: garante 1 valor padrão em cada catálogo de Identificação de produto
 * (produto_estados="Novo", produto_tipos="Acessórios", produto_marcas="Genérica") pra TODA
 * empresa já cadastrada — LandingController::registrar() (e scripts/seed_empresa_eletrocenter.php)
 * já semeiam isso pra empresa NOVA a partir de agora; este script cobre o passado. Pedido do
 * usuário: esses 3 selects (Estado/Tipo/Marca) do cadastro de produto devem ter essa opção
 * padrão pra TODAS as empresas, não só quem já tinha cadastrado algo parecido por conta própria
 * (ver ProdutoController::criar(), que pré-seleciona por nome quando a linha existe).
 *
 * Só mexe em empresa `tipo_conta='completo'` (quem tem o módulo Estoque de verdade — conta
 * só-diretório não usa produto/estoque) e `ativo=1`. Comparação por nome é case-insensitive
 * (mb_strtolower) — se a empresa já tem "novo"/"NOVO"/"Novo" em produto_estados, não duplica;
 * só cria quando não existe nenhuma variação do nome ainda.
 *
 * Por padrão roda em modo SIMULAÇÃO (não grava nada, só conta e mostra amostra). Pra gravar:
 *   php scripts/seed_produto_identificacao_padrao.php --aplicar
 */

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

$aplicar = in_array('--aplicar', $argv, true);
$db = App\Core\DB::pdo();

echo ($aplicar ? "MODO APLICAR — vai gravar de verdade no banco.\n" : "MODO SIMULAÇÃO — nada será gravado (rode com --aplicar pra gravar de verdade).\n");
echo str_repeat('-', 78) . "\n";

// [tabela, nome padrão]
$catalogos = [
    ['produto_estados', 'Novo'],
    ['produto_tipos',   'Acessórios'],
    ['produto_marcas',  'Genérica'],
];

$empresas = $db->query("SELECT id FROM empresas WHERE ativo = 1 AND tipo_conta = 'completo'")->fetchAll(PDO::FETCH_COLUMN);
echo "Empresas com plano completo (usam o módulo Estoque): " . count($empresas) . "\n";

$aCriar = []; // [tabela, empresa_id, nome]
foreach ($catalogos as [$tabela, $nomePadrao]) {
    $existentes = $db->query("SELECT empresa_id, nome FROM `{$tabela}`")->fetchAll();
    $temNaEmpresa = [];
    foreach ($existentes as $row) {
        $temNaEmpresa[(int) $row['empresa_id']][] = mb_strtolower(trim($row['nome']));
    }

    foreach ($empresas as $eid) {
        $eid = (int) $eid;
        $ja = in_array(mb_strtolower($nomePadrao), $temNaEmpresa[$eid] ?? [], true);
        if (!$ja) $aCriar[] = [$tabela, $eid, $nomePadrao];
    }
}

echo "Faltando: " . count($aCriar) . " linhas (empresas × catálogo sem esse valor ainda)\n";
echo str_repeat('-', 78) . "\n";

if (!$aplicar) {
    echo "Amostra das primeiras 15:\n";
    foreach (array_slice($aCriar, 0, 15) as [$tabela, $eid, $nome]) {
        echo "  - empresa #{$eid}: {$tabela} += \"{$nome}\"\n";
    }
    echo "\nRode com --aplicar pra gravar de verdade.\n";
    exit(0);
}

$criados = [];
foreach ($aCriar as [$tabela, $eid, $nome]) {
    try {
        $db->prepare("INSERT IGNORE INTO `{$tabela}` (empresa_id, nome) VALUES (?, ?)")->execute([$eid, $nome]);
        $id = (int) $db->lastInsertId();
        if ($id) $criados[] = [$tabela, $id];
    } catch (\Throwable $e) {
        echo "  FALHA ao criar em {$tabela} pra empresa #{$eid}: " . $e->getMessage() . "\n";
    }
}

echo "Criados: " . count($criados) . "\n";

if ($criados) {
    echo "\nPra desfazer, por tabela:\n";
    $porTabela = [];
    foreach ($criados as [$tabela, $id]) $porTabela[$tabela][] = $id;
    foreach ($porTabela as $tabela => $ids) {
        echo "  DELETE FROM `{$tabela}` WHERE id IN (" . implode(',', $ids) . ");\n";
    }
}
