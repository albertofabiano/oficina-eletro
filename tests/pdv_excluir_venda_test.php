<?php
/*
 * Exclusão de venda do PDV (PdvController::excluir()) e devolução de estoque ao remover
 * peça de OS (OrdemServicoController::removerPeca()) — achado real relatado por cliente
 * (ver CLAUDE.md): não existia nenhuma exclusão de venda no PDV; o cliente "excluía a venda"
 * apagando só o lançamento de receita no Fluxo de Caixa, sem devolver o estoque nem estornar
 * a taxa de cartão. Mesmo gap achado em removerPeca() (OS).
 *
 * Réplica isolada da LÓGICA dos dois métodos contra SQLite em memória (mesma técnica já usada
 * no projeto pra bugs de controller, ex. tests/fixa_caixinhas_test.php) — não instancia os
 * Controllers reais (exigiriam sessão/Auth completos), só reproduz as queries/decisões.
 *
 * Rodar com: php tests/pdv_excluir_venda_test.php
 */

$falhas = 0; $total = 0;
function assert_igual($esperado, $obtido, string $descricao): void
{
    global $falhas, $total;
    $total++;
    if ($esperado === $obtido) { echo "  OK  $descricao\n"; return; }
    $falhas++;
    echo "FALHA $descricao\n      esperado: " . var_export($esperado, true) . "\n      obtido:   " . var_export($obtido, true) . "\n";
}
function assert_verdadeiro(bool $cond, string $descricao): void
{
    global $falhas, $total;
    $total++;
    if ($cond) { echo "  OK  $descricao\n"; return; }
    $falhas++;
    echo "FALHA $descricao\n";
}

function novoBanco(): \PDO
{
    $db = new \PDO('sqlite::memory:');
    $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $db->exec("CREATE TABLE produtos (id INTEGER PRIMARY KEY, empresa_id INTEGER, estoque_atual REAL)");
    $db->exec("CREATE TABLE movimentos_estoque (id INTEGER PRIMARY KEY AUTOINCREMENT, empresa_id INTEGER, produto_id INTEGER, tipo TEXT, quantidade REAL, saldo_anterior REAL, saldo_posterior REAL, valor_unitario REAL, motivo TEXT, os_id INTEGER, venda_id INTEGER)");
    $db->exec("CREATE TABLE pdv_vendas (id INTEGER PRIMARY KEY AUTOINCREMENT, empresa_id INTEGER, lancamento_id INTEGER)");
    $db->exec("CREATE TABLE pdv_venda_itens (id INTEGER PRIMARY KEY AUTOINCREMENT, empresa_id INTEGER, venda_id INTEGER, produto_id INTEGER, quantidade REAL, valor_unitario REAL)");
    $db->exec("CREATE TABLE pdv_venda_pagamentos (id INTEGER PRIMARY KEY AUTOINCREMENT, empresa_id INTEGER, venda_id INTEGER, forma_pagamento TEXT, valor REAL)");
    $db->exec("CREATE TABLE fin_lancamentos (id INTEGER PRIMARY KEY AUTOINCREMENT, empresa_id INTEGER, venda_id INTEGER, tipo TEXT, descricao TEXT, valor REAL)");
    $db->exec("CREATE TABLE os_pecas (id INTEGER PRIMARY KEY AUTOINCREMENT, empresa_id INTEGER, os_id INTEGER, produto_id INTEGER, descricao TEXT, quantidade REAL, valor_unitario REAL)");
    return $db;
}

/** Réplica de Produto::movimentar() (app/Models/Produto.php), fora de um Model real. */
function movimentar(\PDO $db, int $eid, int $produtoId, string $tipo, float $qtd, float $valorUnit, string $motivo, ?int $osId = null, ?int $vendaId = null): void
{
    $stmt = $db->prepare("SELECT estoque_atual FROM produtos WHERE id = ? AND empresa_id = ?");
    $stmt->execute([$produtoId, $eid]);
    $saldoAnt = (float) $stmt->fetchColumn();
    $saldoPost = $tipo === 'entrada' ? $saldoAnt + $qtd : $saldoAnt - $qtd;

    $db->prepare(
        "INSERT INTO movimentos_estoque (empresa_id, produto_id, tipo, quantidade, saldo_anterior, saldo_posterior, valor_unitario, motivo, os_id, venda_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    )->execute([$eid, $produtoId, $tipo, $qtd, $saldoAnt, $saldoPost, $valorUnit, $motivo, $osId, $vendaId]);

    $db->prepare("UPDATE produtos SET estoque_atual = ? WHERE id = ? AND empresa_id = ?")
       ->execute([$saldoPost, $produtoId, $eid]);
}

/** Réplica de PdvController::excluir() (sem o try/transação real, mesma sequência de passos). */
function excluirVenda(\PDO $db, int $eid, int $vendaId): void
{
    $itens = $db->prepare("SELECT produto_id, quantidade, valor_unitario FROM pdv_venda_itens WHERE venda_id = ? AND empresa_id = ?");
    $itens->execute([$vendaId, $eid]);
    foreach ($itens->fetchAll(\PDO::FETCH_ASSOC) as $it) {
        if (!$it['produto_id']) continue;
        movimentar($db, $eid, (int) $it['produto_id'], 'entrada', (float) $it['quantidade'], (float) $it['valor_unitario'], "Estorno da venda PDV #{$vendaId} (excluída)", null, $vendaId);
    }
    $db->prepare("DELETE FROM fin_lancamentos WHERE venda_id = ? AND empresa_id = ?")->execute([$vendaId, $eid]);
    $db->prepare("DELETE FROM pdv_venda_pagamentos WHERE venda_id = ? AND empresa_id = ?")->execute([$vendaId, $eid]);
    $db->prepare("DELETE FROM pdv_venda_itens WHERE venda_id = ? AND empresa_id = ?")->execute([$vendaId, $eid]);
    $db->prepare("DELETE FROM pdv_vendas WHERE id = ? AND empresa_id = ?")->execute([$vendaId, $eid]);
}

/** Réplica de OrdemServicoController::removerPeca() (só a parte de estoque/delete). */
function removerPeca(\PDO $db, int $eid, int $osId, int $itemId): void
{
    $item = $db->prepare("SELECT produto_id, quantidade, valor_unitario FROM os_pecas WHERE id = ? AND os_id = ? AND empresa_id = ?");
    $item->execute([$itemId, $osId, $eid]);
    $peca = $item->fetch(\PDO::FETCH_ASSOC);

    $db->prepare("DELETE FROM os_pecas WHERE id = ? AND os_id = ? AND empresa_id = ?")->execute([$itemId, $osId, $eid]);

    if ($peca && $peca['produto_id']) {
        movimentar($db, $eid, (int) $peca['produto_id'], 'entrada', (float) $peca['quantidade'], (float) $peca['valor_unitario'], "Peça removida da OS #{$osId}", $osId);
    }
}

// ───────────────────────────────────────────────────────────────────────────────────────
echo "== PdvController::excluir() — venda simples (1 item, 1 forma de pagamento) ==\n";
$db = novoBanco();
$eid = 1;
$db->exec("INSERT INTO produtos (id, empresa_id, estoque_atual) VALUES (10, 1, 5)");
movimentar($db, $eid, 10, 'saida', 2, 100, 'Venda PDV #1');
$db->exec("INSERT INTO pdv_vendas (id, empresa_id, lancamento_id) VALUES (1, 1, 1)");
$db->exec("INSERT INTO pdv_venda_itens (venda_id, empresa_id, produto_id, quantidade, valor_unitario) VALUES (1, 1, 10, 2, 100)");
$db->exec("INSERT INTO pdv_venda_pagamentos (venda_id, empresa_id, forma_pagamento, valor) VALUES (1, 1, 'dinheiro', 200)");
$db->exec("INSERT INTO fin_lancamentos (id, venda_id, empresa_id, tipo, descricao, valor) VALUES (1, 1, 1, 'receita', 'Venda PDV #1', 200)");

excluirVenda($db, $eid, 1);

$estoque = $db->query("SELECT estoque_atual FROM produtos WHERE id=10")->fetchColumn();
assert_igual(5.0, (float) $estoque, 'estoque volta ao que era antes da venda (5, era 3 depois de vender 2)');
$lanc = $db->query("SELECT COUNT(*) FROM fin_lancamentos WHERE venda_id=1")->fetchColumn();
assert_igual(0, (int) $lanc, 'lançamento de receita da venda foi apagado');
$itens = $db->query("SELECT COUNT(*) FROM pdv_venda_itens WHERE venda_id=1")->fetchColumn();
assert_igual(0, (int) $itens, 'itens da venda foram apagados');
$venda = $db->query("SELECT COUNT(*) FROM pdv_vendas WHERE id=1")->fetchColumn();
assert_igual(0, (int) $venda, 'a venda em si foi apagada');
$mov = $db->query("SELECT COUNT(*) FROM movimentos_estoque WHERE venda_id=1 AND tipo='entrada'")->fetchColumn();
assert_igual(1, (int) $mov, 'gerou um movimento de ENTRADA vinculado à venda (auditoria, não UPDATE direto)');

echo "\n== PdvController::excluir() — pagamento dividido + taxa de cartão (vários fin_lancamentos) ==\n";
$db = novoBanco();
$db->exec("INSERT INTO produtos (id, empresa_id, estoque_atual) VALUES (20, 1, 10)");
movimentar($db, $eid, 20, 'saida', 1, 300, 'Venda PDV #2');
$db->exec("INSERT INTO pdv_vendas (id, empresa_id, lancamento_id) VALUES (2, 1, 5)");
$db->exec("INSERT INTO pdv_venda_itens (venda_id, empresa_id, produto_id, quantidade, valor_unitario) VALUES (2, 1, 20, 1, 300)");
// receita da parte dinheiro, receita da parte cartão, despesa de taxa do cartão — 3 linhas, só a
// primeira (id=5) ficava salva em pdv_vendas.lancamento_id antes do fix.
$db->exec("INSERT INTO fin_lancamentos (id, venda_id, empresa_id, tipo, descricao, valor) VALUES (5, 2, 1, 'receita', 'Venda PDV #2', 150)");
$db->exec("INSERT INTO fin_lancamentos (id, venda_id, empresa_id, tipo, descricao, valor) VALUES (6, 2, 1, 'receita', 'Venda PDV #2', 150)");
$db->exec("INSERT INTO fin_lancamentos (id, venda_id, empresa_id, tipo, descricao, valor) VALUES (7, 2, 1, 'despesa', 'Taxa cartão — Venda PDV #2', 4.5)");

excluirVenda($db, $eid, 2);

$lancRestantes = $db->query("SELECT COUNT(*) FROM fin_lancamentos WHERE venda_id=2")->fetchColumn();
assert_igual(0, (int) $lancRestantes, 'TODOS os lançamentos da venda (2 receitas + 1 despesa de taxa) foram apagados, não só o primeiro');
$estoque2 = $db->query("SELECT estoque_atual FROM produtos WHERE id=20")->fetchColumn();
assert_igual(10.0, (float) $estoque2, 'estoque devolvido corretamente nesse caso também');

echo "\n== PdvController::excluir() — isolamento por empresa (venda de outra empresa não é afetada) ==\n";
$db = novoBanco();
$db->exec("INSERT INTO produtos (id, empresa_id, estoque_atual) VALUES (30, 2, 8)");
movimentar($db, 2, 30, 'saida', 1, 50, 'Venda PDV #3');
$db->exec("INSERT INTO pdv_vendas (id, empresa_id) VALUES (3, 2)");
$db->exec("INSERT INTO pdv_venda_itens (venda_id, empresa_id, produto_id, quantidade, valor_unitario) VALUES (3, 2, 30, 1, 50)");
$db->exec("INSERT INTO fin_lancamentos (id, venda_id, empresa_id, tipo, descricao, valor) VALUES (9, 3, 2, 'receita', 'Venda PDV #3', 50)");

// empresa 1 tentando excluir uma venda que é da empresa 2 — não deve afetar nada
excluirVenda($db, 1, 3);

$vendaAlheia = $db->query("SELECT COUNT(*) FROM pdv_vendas WHERE id=3")->fetchColumn();
assert_igual(1, (int) $vendaAlheia, 'venda de outra empresa continua intacta (filtro por empresa_id funcionou)');
$estoqueAlheio = $db->query("SELECT estoque_atual FROM produtos WHERE id=30")->fetchColumn();
assert_igual(7.0, (float) $estoqueAlheio, 'estoque de outra empresa não foi mexido (continua debitado, não devolvido por engano)');

echo "\n== OrdemServicoController::removerPeca() — devolve estoque ao remover peça da OS ==\n";
$db = novoBanco();
$db->exec("INSERT INTO produtos (id, empresa_id, estoque_atual) VALUES (40, 1, 6)");
movimentar($db, $eid, 40, 'saida', 1, 80, '', 99);
$db->exec("INSERT INTO os_pecas (id, empresa_id, os_id, produto_id, descricao, quantidade, valor_unitario) VALUES (1, 1, 99, 40, 'Tela Samsung A54', 1, 80)");

removerPeca($db, $eid, 99, 1);

$estoquePeca = $db->query("SELECT estoque_atual FROM produtos WHERE id=40")->fetchColumn();
assert_igual(6.0, (float) $estoquePeca, 'estoque da peça volta ao normal depois de removê-la da OS');
$pecaRestante = $db->query("SELECT COUNT(*) FROM os_pecas WHERE id=1")->fetchColumn();
assert_igual(0, (int) $pecaRestante, 'a peça foi removida da OS');
$movPeca = $db->query("SELECT COUNT(*) FROM movimentos_estoque WHERE os_id=99 AND tipo='entrada'")->fetchColumn();
assert_igual(1, (int) $movPeca, 'gerou um movimento de ENTRADA vinculado à OS');

echo "\n== OrdemServicoController::removerPeca() — peça avulsa (sem produto cadastrado) não quebra ==\n";
$db = novoBanco();
$db->exec("INSERT INTO os_pecas (id, empresa_id, os_id, produto_id, descricao, quantidade, valor_unitario) VALUES (2, 1, 99, NULL, 'Peça avulsa digitada na hora', 1, 50)");
removerPeca($db, $eid, 99, 2);
$pecaAvulsa = $db->query("SELECT COUNT(*) FROM os_pecas WHERE id=2")->fetchColumn();
assert_igual(0, (int) $pecaAvulsa, 'peça avulsa (sem produto_id) é removida normalmente, sem tentar mexer em estoque');

echo "\n$total verificações, $falhas falha(s)\n";
exit($falhas > 0 ? 1 : 0);
