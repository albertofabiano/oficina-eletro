<?php
/*
 * Confirmação estrita de pagamento (PagamentoController::confirmarCobranca(), Item 1 do pedido
 * de cobrança) — testa a FÓRMULA de decisão "isso está pago?" e a idempotência (nunca credita/
 * renova duas vezes), contra os campos REAIS da resposta da InfinitePay, confirmados via
 * scripts/diagnostico_payment_check.php rodado em produção (2026-10-08):
 *
 *   pendente: {"success": false}
 *   pago:     {"success": true, "paid": true, "amount": N, "paid_amount": N,
 *              "installments": N, "capture_method": "pix"}
 *
 * Não chama PagamentoController::confirmarCobranca() de verdade — é `private static` e chama
 * InfinitePayService::verificarPagamento(), que faz uma requisição HTTP real (sem
 * config/infinitepay.php/credencial real neste ambiente, mesma limitação de sempre). Em vez
 * disso, confirmarCobrancaFake() abaixo é uma RÉPLICA FIEL da lógica real — mesma fórmula de
 * decisão (copiada linha a linha do controller), mesmo fluxo de idempotência (reconsulta o
 * status antes de aplicar, nunca reaplica se já está 'pago') — só trocando a chamada de rede
 * por um `$chk` já pronto, que cada teste controla.
 *
 * Rodar com: php tests/pagamento_confirmacao_test.php
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

function novoBancoCobranca(): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("CREATE TABLE empresas (id INTEGER PRIMARY KEY, creditos_os INTEGER DEFAULT 0)");
    $pdo->exec("CREATE TABLE cobrancas (
        id INTEGER PRIMARY KEY AUTOINCREMENT, empresa_id INTEGER, tipo TEXT DEFAULT 'credito',
        plano TEXT, valor INTEGER, order_nsu TEXT, status TEXT DEFAULT 'pendente',
        paid_amount INTEGER, pago_em TEXT
    )");
    return $pdo;
}

/** Só o branch 'credito' de PagamentoController::aplicarEfeitoCobranca() — o suficiente pra
 *  provar que o efeito de negócio não é aplicado duas vezes num "confirmar 2x". */
function aplicarEfeitoCreditoFake(PDO $db, array $c): void
{
    $qtd = (int) preg_replace('/\D/', '', (string) $c['plano']);
    $db->prepare("UPDATE empresas SET creditos_os = creditos_os + ? WHERE id=?")->execute([$qtd, $c['empresa_id']]);
}

/** Réplica fiel de PagamentoController::confirmarCobranca() — mesma fórmula de decisão, mesmo
 *  curto-circuito de idempotência (já pago → não reprocessa), só sem a chamada de rede real. */
function confirmarCobrancaFake(PDO $db, int $cobrancaId, array $chk): bool
{
    $st = $db->prepare("SELECT * FROM cobrancas WHERE id = ?");
    $st->execute([$cobrancaId]);
    $c = $st->fetch();
    if (!$c) return false;
    if ($c['status'] === 'pago') return true;

    $pago = ($chk['success'] ?? null) === true
         && ($chk['paid'] ?? null) === true
         && isset($chk['paid_amount'])
         && (int) $chk['paid_amount'] === (int) $c['valor'];

    if (!$pago) return false;

    $db->prepare("UPDATE cobrancas SET status='pago', paid_amount=?, pago_em='2026-01-01 10:00:00' WHERE id=?")
        ->execute([(int) $chk['paid_amount'], $cobrancaId]);

    aplicarEfeitoCreditoFake($db, $c);
    return true;
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "== Link PENDENTE — payment_check real devolve só {\"success\": false} ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBancoCobranca();
    $pdo->exec("INSERT INTO empresas (id, creditos_os) VALUES (1, 0)");
    $pdo->exec("INSERT INTO cobrancas (id, empresa_id, tipo, plano, valor, order_nsu, status) VALUES (1, 1, 'credito', 'credito_25', 2990, 'fx-1-1', 'pendente')");

    $confirmou = confirmarCobrancaFake($pdo, 1, ['success' => false]);
    assert_verdadeiro(!$confirmou, 'resposta real de link pendente ({"success":false}) não confirma');

    $status = $pdo->query("SELECT status FROM cobrancas WHERE id=1")->fetchColumn();
    assert_igual('pendente', $status, 'cobrança continua pendente no banco');
    $creditos = (int) $pdo->query("SELECT creditos_os FROM empresas WHERE id=1")->fetchColumn();
    assert_igual(0, $creditos, 'nenhum crédito foi dado');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Valor pago diferente do gravado na cobrança — nunca confirma ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBancoCobranca();
    $pdo->exec("INSERT INTO empresas (id, creditos_os) VALUES (1, 0)");
    $pdo->exec("INSERT INTO cobrancas (id, empresa_id, tipo, plano, valor, order_nsu, status) VALUES (1, 1, 'credito', 'credito_25', 2990, 'fx-1-1', 'pendente')");

    // success+paid batem, mas o valor pago é diferente do valor da cobrança gravado no banco —
    // exatamente o cenário que a checagem antiga (`!empty($chk['success'])`) não protegia.
    $confirmou = confirmarCobrancaFake($pdo, 1, ['success' => true, 'paid' => true, 'paid_amount' => 1000]);
    assert_verdadeiro(!$confirmou, 'paid_amount=1000 contra cobrança de 2990: não confirma');
    assert_igual('pendente', $pdo->query("SELECT status FROM cobrancas WHERE id=1")->fetchColumn(), 'continua pendente');
    assert_igual(0, (int) $pdo->query("SELECT creditos_os FROM empresas WHERE id=1")->fetchColumn(), 'nenhum crédito foi dado com valor errado');

    // valor pago MAIOR que o esperado também não confirma — "pago a mais" não é "pago certo".
    $confirmou2 = confirmarCobrancaFake($pdo, 1, ['success' => true, 'paid' => true, 'paid_amount' => 999999]);
    assert_verdadeiro(!$confirmou2, 'paid_amount maior que o esperado também não confirma');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== success=true mas paid ausente/false, ou paid_amount ausente — nunca confirma ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBancoCobranca();
    $pdo->exec("INSERT INTO empresas (id, creditos_os) VALUES (1, 0)");
    $pdo->exec("INSERT INTO cobrancas (id, empresa_id, tipo, plano, valor, order_nsu, status) VALUES (1, 1, 'credito', 'credito_25', 2990, 'fx-1-1', 'pendente')");

    assert_verdadeiro(!confirmarCobrancaFake($pdo, 1, ['success' => true, 'paid_amount' => 2990]), 'success sem paid: não confirma');
    assert_verdadeiro(!confirmarCobrancaFake($pdo, 1, ['success' => true, 'paid' => false, 'paid_amount' => 2990]), 'paid=false explícito: não confirma');
    assert_verdadeiro(!confirmarCobrancaFake($pdo, 1, ['success' => true, 'paid' => true]), 'sem paid_amount nenhum (campo ausente): falha fechado, não confirma');
    assert_verdadeiro(!confirmarCobrancaFake($pdo, 1, ['paid' => true, 'paid_amount' => 2990]), 'paid sem success: não confirma');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Pago de verdade (campos reais de produção, valor batendo) — confirma e credita ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBancoCobranca();
    $pdo->exec("INSERT INTO empresas (id, creditos_os) VALUES (1, 0)");
    $pdo->exec("INSERT INTO cobrancas (id, empresa_id, tipo, plano, valor, order_nsu, status) VALUES (1, 1, 'credito', 'credito_25', 5990, 'fx-1-1', 'pendente')");

    // Mesma forma exata da resposta real confirmada em produção (cobrança paga, id=133).
    $chkReal = ['success' => true, 'paid' => true, 'amount' => 5990, 'paid_amount' => 5990, 'installments' => 1, 'capture_method' => 'pix'];
    $confirmou = confirmarCobrancaFake($pdo, 1, $chkReal);
    assert_verdadeiro($confirmou, 'resposta real de pagamento confirmado: confirma');
    assert_igual('pago', $pdo->query("SELECT status FROM cobrancas WHERE id=1")->fetchColumn(), 'status virou pago');
    assert_igual(25, (int) $pdo->query("SELECT creditos_os FROM empresas WHERE id=1")->fetchColumn(), 'creditou as 25 OS do pacote');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Idempotência: confirmar a MESMA cobrança duas vezes nunca credita/renova duas vezes ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBancoCobranca();
    $pdo->exec("INSERT INTO empresas (id, creditos_os) VALUES (1, 0)");
    $pdo->exec("INSERT INTO cobrancas (id, empresa_id, tipo, plano, valor, order_nsu, status) VALUES (1, 1, 'credito', 'credito_25', 5990, 'fx-1-1', 'pendente')");
    $chkReal = ['success' => true, 'paid' => true, 'amount' => 5990, 'paid_amount' => 5990, 'installments' => 1, 'capture_method' => 'pix'];

    // Simula webhook e retorno() chegando quase ao mesmo tempo pra MESMA cobrança — as duas
    // chamadas acontecem em sequência aqui (sem concorrência real de processo, mas o código
    // real se protege disso com SELECT...FOR UPDATE; aqui testamos que mesmo sem lock, a
    // checagem de 'já está pago' por si só impede reaplicar o efeito).
    $r1 = confirmarCobrancaFake($pdo, 1, $chkReal);
    $r2 = confirmarCobrancaFake($pdo, 1, $chkReal);
    $r3 = confirmarCobrancaFake($pdo, 1, $chkReal);

    assert_verdadeiro($r1 && $r2 && $r3, 'as 3 chamadas retornam "confirmado" (idempotente do ponto de vista de quem chama)');
    assert_igual(25, (int) $pdo->query("SELECT creditos_os FROM empresas WHERE id=1")->fetchColumn(), 'créditos somados só UMA vez, não 3x (continua 25, não 75)');

    $totalCobrancas = (int) $pdo->query("SELECT COUNT(*) FROM cobrancas")->fetchColumn();
    assert_igual(1, $totalCobrancas, 'nenhuma linha de cobrança duplicada');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== order_nsu desconhecido (cobrança não existe aqui) — nunca aplica efeito nenhum ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBancoCobranca();
    $confirmou = confirmarCobrancaFake($pdo, 999, ['success' => true, 'paid' => true, 'paid_amount' => 100]);
    assert_verdadeiro(!$confirmou, 'id de cobrança que não existe: nunca confirma (nada pra confirmar)');
}

echo "\n------------------------------------------------------------\n";
echo "$total verificações, $falhas falha(s).\n";
exit($falhas > 0 ? 1 : 0);
