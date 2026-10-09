<?php
/*
 * "Esta conta/evento é recorrente" no modal SIMPLES de evento (calendario.php) — pedido do
 * usuário: "Adicione os campos inicio e fim com select de meses em eventos recorrentes tambem
 * na lista abaixo de calendario", pro mesmo atalho que já existia no modal de Lançamento
 * (lancamentos.php, ver fixa_recorrentes_test.php) também aparecer quando o item editado na
 * Agenda vem de um molde recorrente — seja uma ocorrência de Eventos Recorrentes
 * (`financeiro_pessoal_eventos.recorrente_id`), seja o evento-lembrete de um lançamento que por
 * sua vez veio de uma Conta Recorrente (`financeiro_pessoal_lancamentos.recorrente_id`, via
 * `lancamento_id`).
 *
 * Este teste replica a query EXATA de FinanceiroPessoalController::buscarEventosDoMes() (método
 * privado, exige sessão/request pra instanciar — mesma limitação de sempre documentada no
 * projeto) contra SQLite em memória, confirmando que o LEFT JOIN expõe `evento_recorrente_id`/
 * `lancamento_recorrente_id` corretamente nos 3 casos (evento recorrente, lançamento recorrente,
 * evento totalmente manual) sem que um vaze pro outro.
 *
 * Rodar com: php tests/fixa_calendario_evento_recorrente_test.php
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

function novoBanco(): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec("CREATE TABLE financeiro_pessoal_lancamentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER NOT NULL,
        recorrente_id INTEGER, descricao TEXT NOT NULL
    )");
    $pdo->exec("CREATE TABLE financeiro_pessoal_eventos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER,
        lancamento_id INTEGER, recorrente_id INTEGER, titulo TEXT NOT NULL, data_hora TEXT NOT NULL
    )");
    return $pdo;
}

/** Réplica exata de FinanceiroPessoalController::buscarEventosDoMes(). */
function buscarEventosDoMes(PDO $db, int $uid, int $perfilId, string $inicioMes, string $fimMes): array
{
    $st = $db->prepare(
        "SELECT e.id, e.titulo, e.data_hora, e.lancamento_id,
                e.recorrente_id AS evento_recorrente_id,
                l.recorrente_id AS lancamento_recorrente_id
         FROM financeiro_pessoal_eventos e
         LEFT JOIN financeiro_pessoal_lancamentos l ON l.id = e.lancamento_id
         WHERE e.usuario_id = ? AND e.perfil_id = ? AND e.data_hora BETWEEN ? AND ?
         ORDER BY e.data_hora ASC"
    );
    $st->execute([$uid, $perfilId, $inicioMes, $fimMes]);
    return $st->fetchAll();
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "== buscarEventosDoMes() expõe o molde certo pros 3 casos, sem vazar um pro outro ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (id, usuario_id, perfil_id, recorrente_id, descricao) VALUES (1, 1, 10, 55, 'Compra de carro parcelado')");
    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (id, usuario_id, perfil_id, recorrente_id, descricao) VALUES (2, 1, 10, NULL, 'Conta avulsa, sem molde')");

    // Caso 1: ocorrência de Eventos Recorrentes (recorrente_id na própria linha do evento).
    $pdo->exec("INSERT INTO financeiro_pessoal_eventos (id, usuario_id, perfil_id, lancamento_id, recorrente_id, titulo, data_hora)
                VALUES (10, 1, 10, NULL, 77, 'Consulta médica', '2026-11-10 09:00:00')");
    // Caso 2: evento-lembrete de um lançamento que veio de Conta Recorrente (lancamento_id
    // aponta pro lançamento 1, cujo recorrente_id = 55 — o JOIN precisa trazer esse 55).
    $pdo->exec("INSERT INTO financeiro_pessoal_eventos (id, usuario_id, perfil_id, lancamento_id, recorrente_id, titulo, data_hora)
                VALUES (11, 1, 10, 1, NULL, 'Compra de carro parcelado — pagamento de R$ 1.200,00 vence hoje', '2026-11-12 08:00:00')");
    // Caso 3: evento-lembrete de um lançamento SEM molde nenhum (lançamento avulso).
    $pdo->exec("INSERT INTO financeiro_pessoal_eventos (id, usuario_id, perfil_id, lancamento_id, recorrente_id, titulo, data_hora)
                VALUES (12, 1, 10, 2, NULL, 'Conta avulsa, sem molde — pagamento de R$ 50,00 vence hoje', '2026-11-15 08:00:00')");
    // Caso 4: evento 100% manual, sem vínculo nenhum (o modal "Evento" já existia pra isso).
    $pdo->exec("INSERT INTO financeiro_pessoal_eventos (id, usuario_id, perfil_id, lancamento_id, recorrente_id, titulo, data_hora)
                VALUES (13, 1, 10, NULL, NULL, 'Aniversário da Maria', '2026-11-20 19:00:00')");

    $eventos = buscarEventosDoMes($pdo, 1, 10, '2026-11-01 00:00:00', '2026-11-30 23:59:59');
    assert_igual(4, count($eventos), '4 eventos do mês, todos retornados');

    $porId = [];
    foreach ($eventos as $e) { $porId[(int) $e['id']] = $e; }

    assert_igual('77', (string) $porId[10]['evento_recorrente_id'], 'evento-recorrente: evento_recorrente_id vem da própria linha');
    assert_igual(null, $porId[10]['lancamento_recorrente_id'], 'evento-recorrente: lancamento_recorrente_id fica nulo (não há lançamento vinculado)');

    assert_igual(null, $porId[11]['evento_recorrente_id'], 'lançamento-recorrente: evento_recorrente_id fica nulo (este evento não é uma ocorrência de Eventos Recorrentes)');
    assert_igual('55', (string) $porId[11]['lancamento_recorrente_id'], 'lançamento-recorrente: lancamento_recorrente_id vem do JOIN com o lançamento vinculado');

    assert_igual(null, $porId[12]['evento_recorrente_id'], 'lançamento avulso (sem molde): evento_recorrente_id nulo');
    assert_igual(null, $porId[12]['lancamento_recorrente_id'], 'lançamento avulso (sem molde): lancamento_recorrente_id também nulo (JOIN achou a linha, mas recorrente_id dela é NULL)');

    assert_igual(null, $porId[13]['evento_recorrente_id'], 'evento 100% manual: evento_recorrente_id nulo');
    assert_igual(null, $porId[13]['lancamento_recorrente_id'], 'evento 100% manual: lancamento_recorrente_id nulo (sem lancamento_id, o LEFT JOIN não acha nada pra trazer)');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Isolamento por usuário/perfil — nunca traz evento de outro escopo ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_eventos (id, usuario_id, perfil_id, lancamento_id, recorrente_id, titulo, data_hora)
                VALUES (1, 1, 10, NULL, 1, 'Evento do usuário 1', '2026-11-05 08:00:00')");
    $pdo->exec("INSERT INTO financeiro_pessoal_eventos (id, usuario_id, perfil_id, lancamento_id, recorrente_id, titulo, data_hora)
                VALUES (2, 2, 20, NULL, 2, 'Evento do usuário 2', '2026-11-06 08:00:00')");

    $eventosU1 = buscarEventosDoMes($pdo, 1, 10, '2026-11-01 00:00:00', '2026-11-30 23:59:59');
    assert_igual(1, count($eventosU1), 'usuário 1 só vê o próprio evento');
    assert_igual('Evento do usuário 1', $eventosU1[0]['titulo'], 'é o evento certo');
}

echo "\n------------------------------------------------------------\n";
echo "$total verificações, $falhas falha(s).\n";
exit($falhas > 0 ? 1 : 0);
