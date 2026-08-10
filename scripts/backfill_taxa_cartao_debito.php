<?php
// Backfill único: gera a despesa "Taxa cartão" + a linha de os_pagamentos que ficaram faltando
// pra OS pagas via cartão de DÉBITO através do atalho "Receber OS" do Fluxo de Caixa, antes da
// correção de FinanceiroController::pagarOs() (ver CLAUDE.md, "Financeiro: taxa de cartão
// faltando no atalho Receber OS").
//
// Só cobre DÉBITO de propósito — crédito fica de fora porque não existe registro de quantas
// parcelas cada venda usou (a taxa varia muito por parcela, ver taxas_cartao em
// config/eventos_agenda.php... na verdade em app/Helpers/functions.php:taxa_cartao_configurada()
// — inventar a parcela seria adivinhação, não correção).
//
// Idempotente: só processa fin_lancamentos que ainda não têm nenhuma linha correspondente em
// os_pagamentos, então rodar de novo não duplica nada.
//
// Por padrão roda em modo SIMULAÇÃO (não grava nada) — só mostra o que faria. Pra gravar de
// verdade:
//   php scripts/backfill_taxa_cartao_debito.php --aplicar

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});
require BASE_PATH . '/app/Helpers/functions.php';

$aplicar = in_array('--aplicar', $argv, true);

$db = App\Core\DB::pdo();

$stmt = $db->query(
    "SELECT fl.id, fl.empresa_id, fl.os_id, fl.cliente_id, fl.conta_id, fl.valor, fl.criado_em,
            os.numero AS os_numero, e.nome_fantasia
     FROM fin_lancamentos fl
     JOIN ordens_servico os ON os.id = fl.os_id
     JOIN empresas e ON e.id = fl.empresa_id
     LEFT JOIN os_pagamentos op ON op.os_id = fl.os_id
     WHERE fl.tipo = 'receita' AND fl.os_id IS NOT NULL
       AND fl.forma_pagamento = 'cartao_debito'
       AND op.id IS NULL"
);
$linhas = $stmt->fetchAll();

if (!$linhas) {
    echo "Nada pra processar — nenhuma receita de débito sem os_pagamentos correspondente.\n";
    exit;
}

echo ($aplicar ? "MODO APLICAR — vai gravar de verdade.\n" : "MODO SIMULAÇÃO — nada será gravado (rode com --aplicar pra gravar).\n");
echo str_repeat('-', 70) . "\n";

foreach ($linhas as $l) {
    $eid       = (int) $l['empresa_id'];
    $taxa      = taxa_cartao_configurada($eid, 'cartao_debito', 1);
    $valor     = (float) $l['valor'];
    $taxaValor = $taxa > 0 ? round($valor * $taxa / 100, 2) : 0.0;

    printf(
        "%s — OS %s (lançamento #%d, %s): valor R$ %s, taxa débito hoje %s%% => R$ %s\n",
        $l['nome_fantasia'], $l['os_numero'], $l['id'], $l['criado_em'],
        number_format($valor, 2, ',', '.'), number_format($taxa, 2, ',', '.'), number_format($taxaValor, 2, ',', '.')
    );

    if ($taxaValor <= 0) {
        echo "  -> taxa de débito configurada é 0% hoje pra essa empresa — nada a lançar (não é falha, pode já ter sido essa a config na época).\n";
        continue;
    }

    if (!$aplicar) {
        echo "  -> (simulação, nada gravado)\n";
        continue;
    }

    $db->prepare(
        "INSERT INTO os_pagamentos
         (empresa_id, os_id, forma_pagamento, valor, parcelas, taxa_percentual, taxa_valor, valor_cobrado)
         VALUES (?, ?, 'cartao_debito', ?, 1, ?, ?, ?)"
    )->execute([$eid, $l['os_id'], $valor, $taxa, $taxaValor, $valor]);

    $catStmt = $db->prepare("SELECT id FROM fin_categorias WHERE empresa_id=? AND tipo='despesa' AND nome='Taxas de cartão' LIMIT 1");
    $catStmt->execute([$eid]);
    $catTaxa = $catStmt->fetchColumn();
    if (!$catTaxa) {
        $db->prepare("INSERT INTO fin_categorias (empresa_id, tipo, nome, cor) VALUES (?, 'despesa', 'Taxas de cartão', '#dc3545')")->execute([$eid]);
        $catTaxa = (int) $db->lastInsertId();
    }

    // Mesma data do lançamento original (não hoje) — o custo da maquininha foi daquele dia.
    $dataRef  = substr((string) $l['criado_em'], 0, 10);
    $descTaxa = 'Taxa cartão — OS ' . $l['os_numero'] . ' (débito · ' . number_format($taxa, 2, ',', '.') . '%) [lançado retroativamente]';

    $db->prepare(
        "INSERT INTO fin_lancamentos
         (empresa_id, conta_id, categoria_id, os_id, cliente_id, tipo, descricao,
          valor, data_vencimento, data_pagamento, status, forma_pagamento)
         VALUES (?, ?, ?, ?, ?, 'despesa', ?, ?, ?, ?, 'pago', 'cartao_debito')"
    )->execute([
        $eid, $l['conta_id'], $catTaxa, $l['os_id'], $l['cliente_id'],
        $descTaxa, $taxaValor, $dataRef, $dataRef,
    ]);

    echo "  -> despesa lançada.\n";
}

echo str_repeat('-', 70) . "\n";
echo "Concluído.\n";
