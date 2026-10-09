<?php
/*
 * "Unindo a Agenda às notificações" (pedido do usuário) — antes desta mudança, só lançamento
 * gerado por conta recorrente ganhava um evento-lembrete em `financeiro_pessoal_eventos`
 * (criado por RecorrenteService::gerarPendentes()); um lançamento MANUAL com vencimento nunca
 * disparava o sino/toast "chegou" no dia, só aparecia na lista de vencidos DEPOIS de atrasado.
 * Achados e corrigidos no caminho: pagar uma conta não limpava a notificação pendente dela, e
 * excluir um lançamento deixava um evento "fantasma" (FK ON DELETE SET NULL só soltava o
 * vínculo, não apagava o evento).
 *
 * Este teste replica a lógica EXATA de FinanceiroPessoalController::sincronizarEventoDoLancamento()
 * (método embutido no controller, que exige sessão/request pra instanciar — não dá pra chamar
 * direto, mesma limitação de sempre documentada no projeto) contra SQLite em memória, pra provar
 * o comportamento sem precisar de banco/servidor de verdade.
 *
 * Rodar com: php tests/fixa_notificacao_lancamento_test.php
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

function novoBanco(): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec("CREATE TABLE financeiro_pessoal_lancamentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER NOT NULL,
        tipo TEXT DEFAULT 'despesa', descricao TEXT NOT NULL, valor REAL NOT NULL,
        vencimento TEXT, pago_em TEXT
    )");
    $pdo->exec("CREATE TABLE financeiro_pessoal_eventos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER,
        lancamento_id INTEGER, recorrente_id INTEGER, titulo TEXT NOT NULL, data_hora TEXT NOT NULL,
        lido_em TEXT
    )");
    return $pdo;
}

/** Réplica exata de FinanceiroPessoalController::sincronizarEventoDoLancamento(). */
function sincronizarEventoDoLancamento(PDO $db, int $uid, int $perfilId, int $lancamentoId): void
{
    $l = $db->prepare(
        "SELECT descricao, valor, tipo, vencimento, pago_em FROM financeiro_pessoal_lancamentos
         WHERE id = ? AND usuario_id = ? AND perfil_id = ?"
    );
    $l->execute([$lancamentoId, $uid, $perfilId]);
    $lanc = $l->fetch();
    if (!$lanc) return;

    $existe = $db->prepare("SELECT id, data_hora FROM financeiro_pessoal_eventos WHERE lancamento_id = ?");
    $existe->execute([$lancamentoId]);
    $eventoAtual = $existe->fetch();

    if (!$lanc['vencimento'] || $lanc['pago_em']) {
        if ($eventoAtual) {
            $db->prepare("DELETE FROM financeiro_pessoal_eventos WHERE id = ?")->execute([$eventoAtual['id']]);
        }
        return;
    }

    $dataHora = $lanc['vencimento'] . ' 08:00:00';
    $tipoLabel = $lanc['tipo'] === 'receita' ? 'recebimento' : 'pagamento';
    $titulo = mb_substr(
        $lanc['descricao'] . ' — ' . $tipoLabel . ' de R$ ' . number_format((float) $lanc['valor'], 2, ',', '.') . ' vence hoje',
        0, 150
    );

    if ($eventoAtual) {
        $mudouData = substr($eventoAtual['data_hora'], 0, 10) !== $lanc['vencimento'];
        $sql = "UPDATE financeiro_pessoal_eventos SET titulo = ?, data_hora = ?"
             . ($mudouData ? ", lido_em = NULL" : "") . " WHERE id = ?";
        $db->prepare($sql)->execute([$titulo, $dataHora, $eventoAtual['id']]);
    } else {
        $db->prepare(
            "INSERT INTO financeiro_pessoal_eventos (usuario_id, perfil_id, lancamento_id, titulo, data_hora)
             VALUES (?, ?, ?, ?, ?)"
        )->execute([$uid, $perfilId, $lancamentoId, $titulo, $dataHora]);
    }
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "== Lançamento sem vencimento nunca ganha evento-lembrete ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (id, usuario_id, perfil_id, descricao, valor, vencimento) VALUES (1, 1, 10, 'Gasolina', 150.00, NULL)");
    sincronizarEventoDoLancamento($pdo, 1, 10, 1);
    $qtdEventos = (int) $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_eventos")->fetchColumn();
    assert_igual(0, $qtdEventos, 'sem vencimento, nenhum evento criado');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Lançamento MANUAL com vencimento ganha evento (antes só recorrente ganhava) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (id, usuario_id, perfil_id, tipo, descricao, valor, vencimento) VALUES (1, 1, 10, 'despesa', 'Internet', 100.00, '2026-10-15')");
    sincronizarEventoDoLancamento($pdo, 1, 10, 1);

    $ev = $pdo->query("SELECT * FROM financeiro_pessoal_eventos WHERE lancamento_id = 1")->fetch();
    assert_verdadeiro($ev !== false, 'evento criado pra lançamento manual com vencimento');
    assert_igual('2026-10-15 08:00:00', $ev['data_hora'], 'data/hora do evento = vencimento às 08:00');
    assert_igual('Internet — pagamento de R$ 100,00 vence hoje', $ev['titulo'], 'título no mesmo formato já usado por RecorrenteService::gerarPendentes()');
    assert_igual(null, $ev['lido_em'], 'nasce não lido');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Editar: mudar a data reabre a notificação; só mudar a descrição não ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (id, usuario_id, perfil_id, tipo, descricao, valor, vencimento) VALUES (1, 1, 10, 'despesa', 'Internet', 100.00, '2026-10-15')");
    sincronizarEventoDoLancamento($pdo, 1, 10, 1);
    // Simula o usuário já ter marcado como lida no sino.
    $pdo->exec("UPDATE financeiro_pessoal_eventos SET lido_em = '2026-10-15 09:00:00' WHERE lancamento_id = 1");

    // Só troca a descrição, vencimento continua o mesmo.
    $pdo->exec("UPDATE financeiro_pessoal_lancamentos SET descricao = 'Internet fibra' WHERE id = 1");
    sincronizarEventoDoLancamento($pdo, 1, 10, 1);
    $ev1 = $pdo->query("SELECT * FROM financeiro_pessoal_eventos WHERE lancamento_id = 1")->fetch();
    assert_verdadeiro(str_contains($ev1['titulo'], 'Internet fibra'), 'título atualizado com a nova descrição');
    assert_verdadeiro($ev1['lido_em'] !== null, 'mesma data de vencimento: lido_em preservado (não reabre notificação já vista à toa)');

    // Agora muda o vencimento de verdade (conta foi adiada).
    $pdo->exec("UPDATE financeiro_pessoal_lancamentos SET vencimento = '2026-10-20' WHERE id = 1");
    sincronizarEventoDoLancamento($pdo, 1, 10, 1);
    $ev2 = $pdo->query("SELECT * FROM financeiro_pessoal_eventos WHERE lancamento_id = 1")->fetch();
    assert_igual('2026-10-20 08:00:00', $ev2['data_hora'], 'data do evento acompanha o novo vencimento');
    assert_igual(null, $ev2['lido_em'], 'vencimento mudou: volta a não-lido, precisa avisar de novo');
    assert_igual(
        (int) $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_eventos")->fetchColumn(), 1,
        'continua 1 evento só (UPDATE em cima do existente, não duplica)'
    );
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Marcar como pago remove a notificação pendente; desmarcar recria ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (id, usuario_id, perfil_id, tipo, descricao, valor, vencimento) VALUES (1, 1, 10, 'despesa', 'Internet', 100.00, '2026-10-15')");
    sincronizarEventoDoLancamento($pdo, 1, 10, 1);
    assert_igual(1, (int) $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_eventos")->fetchColumn(), 'pré-condição: evento existe');

    // marcarPago() — grava pago_em e chama o sync de novo.
    $pdo->exec("UPDATE financeiro_pessoal_lancamentos SET pago_em = '2026-10-14' WHERE id = 1");
    sincronizarEventoDoLancamento($pdo, 1, 10, 1);
    assert_igual(0, (int) $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_eventos")->fetchColumn(), 'pago: notificação pendente some sozinha');

    // desmarcarPago() — reabre, volta a precisar de lembrete.
    $pdo->exec("UPDATE financeiro_pessoal_lancamentos SET pago_em = NULL WHERE id = 1");
    sincronizarEventoDoLancamento($pdo, 1, 10, 1);
    $evReaberto = $pdo->query("SELECT * FROM financeiro_pessoal_eventos WHERE lancamento_id = 1")->fetch();
    assert_verdadeiro($evReaberto !== false, 'reabriu o pagamento: evento recriado');
    assert_igual(null, $evReaberto['lido_em'], 'evento recriado nasce não lido');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Excluir o lançamento apaga o evento junto (não vira órfão) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (id, usuario_id, perfil_id, tipo, descricao, valor, vencimento) VALUES (1, 1, 10, 'despesa', 'Internet', 100.00, '2026-10-15')");
    sincronizarEventoDoLancamento($pdo, 1, 10, 1);
    assert_igual(1, (int) $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_eventos")->fetchColumn(), 'pré-condição: evento existe');

    // Réplica do que excluir() faz agora: apaga o evento ANTES de apagar o lançamento.
    $pdo->prepare("DELETE FROM financeiro_pessoal_eventos WHERE lancamento_id = ? AND usuario_id = ?")->execute([1, 1]);
    $pdo->prepare("DELETE FROM financeiro_pessoal_lancamentos WHERE id = ? AND usuario_id = ? AND perfil_id = ?")->execute([1, 1, 10]);

    assert_igual(0, (int) $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_eventos")->fetchColumn(), 'evento removido junto, sem deixar fantasma');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Evento de conta recorrente (lancamento_id + recorrente_id juntos) também sincroniza ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    // RecorrenteService::gerarPendentes() cria o evento direto (fora deste helper); o teste
    // confirma que sincronizarEventoDoLancamento() consegue ASSUMIR esse evento já existente
    // (ex.: ao editar depois pela tela normal de Lançamento) sem duplicar.
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (id, usuario_id, perfil_id, tipo, descricao, valor, vencimento) VALUES (1, 1, 10, 'despesa', 'Aluguel', 1500.00, '2026-10-05')");
    $pdo->exec("INSERT INTO financeiro_pessoal_eventos (id, usuario_id, perfil_id, lancamento_id, recorrente_id, titulo, data_hora) VALUES (1, 1, 10, 1, 7, 'Aluguel — pagamento de R$ 1.500,00 vence hoje', '2026-10-05 08:00:00')");

    $pdo->exec("UPDATE financeiro_pessoal_lancamentos SET vencimento = '2026-10-08' WHERE id = 1");
    sincronizarEventoDoLancamento($pdo, 1, 10, 1);

    $ev = $pdo->query("SELECT * FROM financeiro_pessoal_eventos WHERE lancamento_id = 1")->fetch();
    assert_igual(1, (int) $ev['id'], 'atualiza o evento já existente (mesmo id), não cria um segundo');
    assert_igual('2026-10-08 08:00:00', $ev['data_hora'], 'data acompanha a edição feita pela tela normal de Lançamento');
    assert_igual(7, (int) $ev['recorrente_id'], 'recorrente_id (vínculo com o molde) preservado, UPDATE não mexeu nessa coluna');
}

echo "\n------------------------------------------------------------\n";
echo "$total verificações, $falhas falha(s).\n";
exit($falhas > 0 ? 1 : 0);
