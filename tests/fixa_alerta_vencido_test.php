<?php
/*
 * Testa a REGRA de elegibilidade do alerta de lançamento vencido
 * (FinanceiroPessoalController::alertasVencidosAjax()) contra SQLite em memória.
 *
 * A query real usa `DATE_SUB(NOW(), INTERVAL 3 HOUR)`, sintaxe específica do MySQL que o
 * parser do SQLite não entende (não é sobre faltar a função, é a gramática `INTERVAL N UNIT`
 * mesmo) — por isso a mesma condição lógica é expressa aqui com `datetime(..., '+3 hours')`,
 * SQLite-nativo. Qualquer mudança na regra do controller precisa ser replicada aqui também,
 * senão o teste para de significar o que diz que significa.
 *
 * Cobre: vencido nunca alertado entra; vencido alertado há <3h não entra (ainda no throttle);
 * vencido alertado há >3h entra de novo; pago não entra; vencimento futuro não entra; perfil
 * errado não entra (isolamento).
 *
 * Rodar com: php tests/fixa_alerta_vencido_test.php
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

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$pdo->exec("CREATE TABLE financeiro_pessoal_lancamentos (
    id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER NOT NULL,
    descricao TEXT NOT NULL, valor REAL NOT NULL, vencimento TEXT, pago_em TEXT,
    ultimo_alerta_vencido_em TEXT
)");

$hoje = new DateTime('today');
function dias_atras(DateTime $hoje, int $n): string { return (clone $hoje)->modify("-{$n} days")->format('Y-m-d'); }
function horas_atras(int $n): string { return (new DateTime())->modify("-{$n} hours")->format('Y-m-d H:i:s'); }

$pdo->exec("INSERT INTO financeiro_pessoal_lancamentos
    (id, usuario_id, perfil_id, descricao, valor, vencimento, pago_em, ultimo_alerta_vencido_em) VALUES
    (1, 42, 1, 'Nunca alertado',        100.00, '" . dias_atras($hoje, 3) . "', NULL, NULL),
    (2, 42, 1, 'Alertado há 1h',        50.00,  '" . dias_atras($hoje, 5) . "', NULL, '" . horas_atras(1) . "'),
    (3, 42, 1, 'Alertado há 4h',        75.00,  '" . dias_atras($hoje, 5) . "', NULL, '" . horas_atras(4) . "'),
    (4, 42, 1, 'Já pago',               30.00,  '" . dias_atras($hoje, 3) . "', '" . dias_atras($hoje, 1) . "', NULL),
    (5, 42, 1, 'Vence no futuro',       20.00,  '" . (clone $hoje)->modify('+3 days')->format('Y-m-d') . "', NULL, NULL),
    (6, 42, 1, 'Vence hoje (não é vencido ainda)', 10.00, '" . $hoje->format('Y-m-d') . "', NULL, NULL),
    (7, 99, 2, 'Outro usuário/perfil',  999.00, '" . dias_atras($hoje, 3) . "', NULL, NULL)
");

// Mesma condição de alertasVencidosAjax(), só com `datetime('now', '-3 hours')` no lugar de
// `DATE_SUB(NOW(), INTERVAL 3 HOUR)` (equivalente lógico, sintaxe SQLite).
$st = $pdo->prepare(
    "SELECT id FROM financeiro_pessoal_lancamentos
     WHERE usuario_id = ? AND perfil_id = ? AND pago_em IS NULL
       AND vencimento IS NOT NULL AND vencimento < date('now')
       AND (ultimo_alerta_vencido_em IS NULL OR ultimo_alerta_vencido_em < datetime('now', '-3 hours'))
     ORDER BY vencimento ASC"
);
$st->execute([42, 1]);
$ids = array_column($st->fetchAll(), 'id');

// id=3 vence há 5 dias (antes de id=1, que vence há 3) — ORDER BY vencimento ASC traz o mais
// antigo primeiro.
assert_igual([3, 1], $ids, 'só "nunca alertado" e "alertado há 4h" entram nesta rodada, mais vencido primeiro');
assert_igual(false, in_array(2, $ids, true), '"alertado há 1h" NÃO entra (ainda dentro do throttle de 3h)');
assert_igual(false, in_array(4, $ids, true), 'lançamento já pago nunca entra');
assert_igual(false, in_array(5, $ids, true), 'vencimento no futuro nunca entra');
assert_igual(false, in_array(6, $ids, true), 'vence HOJE ainda não conta como vencido (< hoje, não <=)');
assert_igual(false, in_array(7, $ids, true), 'lançamento de outro usuário/perfil nunca entra (isolamento)');

// ── Depois de "alertar" (simula o UPDATE que o controller faz em seguida), o mesmo lançamento
// não deve aparecer de novo numa consulta imediatamente posterior. ──────────────────────────
$pdo->prepare("UPDATE financeiro_pessoal_lancamentos SET ultimo_alerta_vencido_em = datetime('now') WHERE id IN (1, 3)")->execute();
$st->execute([42, 1]);
$idsDepois = array_column($st->fetchAll(), 'id');
assert_igual([], $idsDepois, 'depois de marcar como alertado agora, nenhum dos dois aparece de novo na hora');

echo "\n" . str_repeat('-', 60) . "\n";
echo $total . " verificações, " . $falhas . " falha(s).\n";
exit($falhas > 0 ? 1 : 0);
