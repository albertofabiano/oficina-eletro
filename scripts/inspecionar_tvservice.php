<?php
/**
 * Só LEITURA — nenhum INSERT/UPDATE/DELETE neste arquivo. Levanta um retrato dos dados da
 * empresa "TV Service" (categorias de equipamento, técnicos, status de OS, volume/faturamento
 * recente, catálogo de peças) pra servir de referência ao redesenhar a empresa de teste
 * "Eletrocenter" com um perfil parecido — sem tocar em nada da TVSERVICE.
 *
 * USO:
 *   php scripts/inspecionar_tvservice.php
 *   php scripts/inspecionar_tvservice.php --empresa=ID   (se o nome bater com mais de uma empresa)
 */

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

$argOpt = function (string $nome, $default) use ($argv) {
    foreach ($argv as $a) {
        if (str_starts_with($a, "--{$nome}=")) return substr($a, strlen($nome) + 3);
    }
    return $default;
};
$empresaArg = $argOpt('empresa', null);

$db = App\Core\DB::pdo();

if ($empresaArg !== null) {
    $stmt = $db->prepare("SELECT id, nome_fantasia, razao_social FROM empresas WHERE id = ?");
    $stmt->execute([(int) $empresaArg]);
    $empresa = $stmt->fetch();
    if (!$empresa) { fwrite(STDERR, "Empresa #{$empresaArg} não encontrada.\n"); exit(1); }
} else {
    $stmt = $db->query("SELECT id, nome_fantasia, razao_social FROM empresas WHERE nome_fantasia LIKE '%TV Service%' OR razao_social LIKE '%TV Service%'");
    $candidatos = $stmt->fetchAll();
    if (count($candidatos) === 0) { fwrite(STDERR, "Nenhuma empresa com nome contendo 'TV Service' encontrada.\n"); exit(1); }
    if (count($candidatos) > 1) {
        fwrite(STDERR, "Mais de uma empresa bateu — escolha uma com --empresa=ID:\n");
        foreach ($candidatos as $c) fwrite(STDERR, "  #{$c['id']} — {$c['nome_fantasia']} ({$c['razao_social']})\n");
        exit(1);
    }
    $empresa = $candidatos[0];
}
$eid = (int) $empresa['id'];

echo "========================================================================\n";
echo "Empresa: #{$eid} — {$empresa['nome_fantasia']} ({$empresa['razao_social']})\n";
echo "SOMENTE LEITURA — nada foi ou será alterado nesta empresa.\n";
echo "========================================================================\n\n";

echo "-- Categorias de equipamento --\n";
$stmt = $db->prepare("SELECT nome, icone FROM categorias_equipamento WHERE empresa_id = ? ORDER BY nome");
$stmt->execute([$eid]);
foreach ($stmt->fetchAll() as $r) echo "  - {$r['nome']} ({$r['icone']})\n";

echo "\n-- Tipos de equipamento realmente usados em equipamentos.tipo (top 20) --\n";
$stmt = $db->prepare("SELECT tipo, COUNT(*) AS n FROM equipamentos WHERE empresa_id = ? GROUP BY tipo ORDER BY n DESC LIMIT 20");
$stmt->execute([$eid]);
foreach ($stmt->fetchAll() as $r) echo "  - {$r['tipo']}: {$r['n']}\n";

echo "\n-- Marcas mais frequentes (top 20) --\n";
$stmt = $db->prepare("SELECT marca, COUNT(*) AS n FROM equipamentos WHERE empresa_id = ? AND marca IS NOT NULL AND marca <> '' GROUP BY marca ORDER BY n DESC LIMIT 20");
$stmt->execute([$eid]);
foreach ($stmt->fetchAll() as $r) echo "  - {$r['marca']}: {$r['n']}\n";

echo "\n-- Técnicos ativos (perfil='tecnico' OU atende_os=1) --\n";
$stmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE empresa_id = ? AND ativo = 1 AND (perfil = 'tecnico' OR atende_os = 1)");
$stmt->execute([$eid]);
echo "  Total: " . $stmt->fetchColumn() . "\n";

echo "\n-- Status de OS cadastrados --\n";
$stmt = $db->prepare("SELECT nome, tipo, permite_fechar, sem_valor FROM os_status WHERE empresa_id = ? ORDER BY ordem");
$stmt->execute([$eid]);
foreach ($stmt->fetchAll() as $r) echo "  - {$r['nome']} (tipo={$r['tipo']}, permite_fechar={$r['permite_fechar']}, sem_valor={$r['sem_valor']})\n";

echo "\n-- Volume de OS por mês (últimos 6 meses, por data_entrada) --\n";
$stmt = $db->prepare("
    SELECT DATE_FORMAT(data_entrada,'%Y-%m') AS mes, COUNT(*) AS qtd, COALESCE(SUM(valor_total),0) AS total
    FROM ordens_servico WHERE empresa_id = ? AND data_entrada >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY mes ORDER BY mes
");
$stmt->execute([$eid]);
foreach ($stmt->fetchAll() as $r) echo "  - {$r['mes']}: {$r['qtd']} OS, valor_total somado R$ " . number_format((float) $r['total'], 2, ',', '.') . "\n";

echo "\n-- Faturamento real no Financeiro por mês (últimos 6 meses, fin_lancamentos receita paga) --\n";
$stmt = $db->prepare("
    SELECT DATE_FORMAT(data_pagamento,'%Y-%m') AS mes, COUNT(*) AS qtd, SUM(valor) AS total
    FROM fin_lancamentos WHERE empresa_id = ? AND tipo='receita' AND status='pago'
      AND data_pagamento >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY mes ORDER BY mes
");
$stmt->execute([$eid]);
foreach ($stmt->fetchAll() as $r) echo "  - {$r['mes']}: {$r['qtd']} lançamentos, R$ " . number_format((float) $r['total'], 2, ',', '.') . "\n";

echo "\n-- Ticket médio (valor_total > 0, últimos 6 meses) --\n";
$stmt = $db->prepare("
    SELECT COUNT(*) AS n, AVG(valor_total) AS media FROM ordens_servico
    WHERE empresa_id = ? AND valor_total > 0 AND data_entrada >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
");
$stmt->execute([$eid]);
$r = $stmt->fetch();
echo "  {$r['n']} OS com valor > 0, ticket médio R$ " . number_format((float) $r['media'], 2, ',', '.') . "\n";

echo "\n-- Distribuição de status atual das OS (contagem por tipo) --\n";
$stmt = $db->prepare("
    SELECT s.tipo, COUNT(*) AS n FROM ordens_servico o JOIN os_status s ON s.id = o.status_id
    WHERE o.empresa_id = ? GROUP BY s.tipo ORDER BY n DESC
");
$stmt->execute([$eid]);
foreach ($stmt->fetchAll() as $r) echo "  - {$r['tipo']}: {$r['n']}\n";

echo "\n-- Produtos/peças em estoque: total e por categoria --\n";
$stmt = $db->prepare("SELECT COUNT(*) FROM produtos WHERE empresa_id = ?");
$stmt->execute([$eid]);
echo "  Total: " . $stmt->fetchColumn() . "\n";
$stmt = $db->prepare("
    SELECT cp.nome, COUNT(*) AS n FROM produtos p LEFT JOIN categorias_produto cp ON cp.id = p.categoria_id
    WHERE p.empresa_id = ? GROUP BY cp.nome ORDER BY n DESC LIMIT 20
");
$stmt->execute([$eid]);
foreach ($stmt->fetchAll() as $r) echo "  - " . ($r['nome'] ?? '(sem categoria)') . ": {$r['n']}\n";

echo "\n-- Total de clientes --\n";
$stmt = $db->prepare("SELECT COUNT(*) FROM clientes WHERE empresa_id = ?");
$stmt->execute([$eid]);
echo "  Total: " . $stmt->fetchColumn() . "\n";

echo "\n-- Contas e categorias do Financeiro --\n";
$stmt = $db->prepare("SELECT nome, tipo FROM fin_contas WHERE empresa_id = ?");
$stmt->execute([$eid]);
foreach ($stmt->fetchAll() as $r) echo "  Conta: {$r['nome']} ({$r['tipo']})\n";
$stmt = $db->prepare("SELECT tipo, nome FROM fin_categorias WHERE empresa_id = ? ORDER BY tipo, nome");
$stmt->execute([$eid]);
foreach ($stmt->fetchAll() as $r) echo "  Categoria [{$r['tipo']}]: {$r['nome']}\n";

echo "\n========================================================================\n";
echo "Fim — nenhum dado da TVSERVICE foi alterado por este script.\n";
