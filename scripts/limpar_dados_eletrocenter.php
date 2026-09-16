<?php
/**
 * Apaga TODOS os dados de movimento da empresa fictícia "Eletrocenter" (clientes, OS,
 * equipamentos, lançamentos financeiros, produtos, catálogo de serviços), deixando só a
 * empresa em si intacta: login(s) (admin + qualquer usuário que você tenha criado depois pela
 * tela), e o esqueleto (os_status, categorias_equipamento, fin_contas, fin_categorias,
 * produto_estados/tipos/marcas, configuracoes, créditos de marketplace).
 *
 * Ordem de exclusão respeita as FKs do schema (confirmado em database/migrations/001_schema.sql):
 * `ordens_servico.cliente_id`/`equipamento_id` NÃO têm ON DELETE CASCADE (ao contrário do que se
 * poderia supor) — apagar clientes/equipamentos antes das OS que os referenciam quebra com erro
 * de FK. A ordem certa é: movimentos_estoque -> fin_lancamentos -> ordens_servico (cascade
 * automático pra os_historico/os_servicos/os_pecas/os_adiantamentos/os_pagamentos, todos com
 * ON DELETE CASCADE em os_id) -> equipamentos -> clientes -> produtos -> categorias_produto ->
 * servicos_catalogo.
 *
 * Por padrão roda em modo SIMULAÇÃO (só mostra quantas linhas apagaria). Pra apagar de verdade:
 *   php scripts/limpar_dados_eletrocenter.php --aplicar
 *
 * Opções:
 *   --empresa=ID   força o id da empresa (padrão: busca por nome_fantasia/razao_social LIKE '%Eletrocenter%')
 */

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

$aplicar = in_array('--aplicar', $argv, true);

$argOpt = function (string $nome, $default) use ($argv) {
    foreach ($argv as $a) {
        if (str_starts_with($a, "--{$nome}=")) return substr($a, strlen($nome) + 3);
    }
    return $default;
};
$empresaArg = $argOpt('empresa', null);

$db = App\Core\DB::pdo();

echo ($aplicar ? "MODO APLICAR — vai apagar de verdade no banco.\n" : "MODO SIMULAÇÃO — nada será apagado (rode com --aplicar pra apagar de verdade).\n");
echo str_repeat('-', 78) . "\n";

// ---------------------------------------------------------------------------------------
// Resolve empresa
// ---------------------------------------------------------------------------------------

if ($empresaArg !== null) {
    $stmt = $db->prepare("SELECT id, nome_fantasia, razao_social FROM empresas WHERE id = ?");
    $stmt->execute([(int) $empresaArg]);
    $empresa = $stmt->fetch();
    if (!$empresa) { fwrite(STDERR, "Empresa #{$empresaArg} não encontrada.\n"); exit(1); }
} else {
    $stmt = $db->query(
        "SELECT id, nome_fantasia, razao_social FROM empresas
         WHERE nome_fantasia LIKE '%Eletrocenter%' OR razao_social LIKE '%Eletrocenter%'"
    );
    $candidatos = $stmt->fetchAll();
    if (count($candidatos) === 0) { fwrite(STDERR, "Empresa 'Eletrocenter' não encontrada.\n"); exit(1); }
    if (count($candidatos) > 1) {
        fwrite(STDERR, "Mais de uma empresa bateu com 'Eletrocenter' — escolha uma com --empresa=ID:\n");
        foreach ($candidatos as $c) fwrite(STDERR, "  #{$c['id']} — {$c['nome_fantasia']} ({$c['razao_social']})\n");
        exit(1);
    }
    $empresa = $candidatos[0];
}
$eid = (int) $empresa['id'];
echo "Empresa alvo: #{$eid} — {$empresa['nome_fantasia']} ({$empresa['razao_social']})\n";
echo str_repeat('-', 78) . "\n";

// ---------------------------------------------------------------------------------------
// Tabelas a apagar por completo, na ordem correta (a próxima só é segura depois da anterior)
// ---------------------------------------------------------------------------------------

$tabelas = [
    'movimentos_estoque',
    'fin_lancamentos',
    'ordens_servico',   // cascade: os_historico, os_servicos, os_pecas, os_adiantamentos, os_pagamentos
    'equipamentos',
    'clientes',
    'produtos',
    'categorias_produto',
    'servicos_catalogo',
];

// Preserva de propósito (NÃO apagar): empresas, usuarios, os_status, categorias_equipamento,
// fin_contas, fin_categorias, configuracoes, produto_estados/tipos/marcas, equip_acessorios,
// marketplace_creditos, marketplace_historico_creditos, crm_estagios.

echo "Contagem atual (nada apagado ainda):\n";
$contagens = [];
foreach ($tabelas as $t) {
    $stmtCount = $db->prepare("SELECT COUNT(*) FROM `{$t}` WHERE empresa_id = ?");
    $stmtCount->execute([$eid]);
    $n = (int) $stmtCount->fetchColumn();
    $contagens[$t] = $n;
    echo "  - {$t}: {$n}\n";
}
echo str_repeat('-', 78) . "\n";

echo "Preservado (não é tocado por este script): empresas, usuarios, os_status,\n";
echo "categorias_equipamento, fin_contas, fin_categorias, configuracoes,\n";
echo "produto_estados/tipos/marcas, equip_acessorios, marketplace_creditos/historico, crm_estagios.\n";
echo str_repeat('-', 78) . "\n";

if (!$aplicar) {
    echo "Rode com --aplicar pra apagar de verdade.\n";
    exit(0);
}

$db->beginTransaction();
try {
    foreach ($tabelas as $t) {
        $db->prepare("DELETE FROM `{$t}` WHERE empresa_id = ?")->execute([$eid]);
        echo "Apagado: {$t} ({$contagens[$t]} linhas)\n";
    }
    $db->commit();
    echo str_repeat('-', 78) . "\n";
    echo "Concluído. Empresa #{$eid} zerada — só a empresa, o(s) login(s) e o esqueleto continuam.\n";
} catch (Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, "Erro — nada foi apagado (rollback): " . $e->getMessage() . "\n");
    exit(1);
}
