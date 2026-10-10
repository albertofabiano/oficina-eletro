<?php
/*
 * Testa a correção do bug real reportado pelo usuário (print do modal "Evento(s) não
 * concluído(s)" mostrando cada evento duplicado): AgendaLembreteService::enviarAlertasPendentes()
 * é chamado por DOIS caminhos independentes, cada um ~1x/min mas em relógios que não se
 * sincronizam entre si — scripts/processar_lembretes_agenda.php (cron real) e
 * AgendaLembreteService::processarFilaThrottled() (poller disparado por tráfego web). Se os dois
 * caírem quase juntos, a versão antiga (SELECT solto, sem lock, UPDATE só depois do INSERT)
 * deixa os dois lerem "elegível" antes de qualquer um gravar `ultimo_alerta_pendente_em`,
 * duplicando o alerta — mesma categoria de corrida já corrigida antes em
 * OrdemServicoController::fechar()/adicionarAdiantamento() (ver CLAUDE.md).
 *
 * `FOR UPDATE` (MySQL) não existe no SQLite, então aqui não roda a query real literal — replica
 * a MESMA lógica (SELECT solto + recheck da mesma condição por id antes de inserir) com sintaxe
 * SQLite-equivalente, igual ao padrão já usado em tests/fixa_alerta_vencido_test.php. O que este
 * teste prova é a garantia central da correção: rechecar a condição de elegibilidade por id,
 * IMEDIATAMENTE antes de inserir, é o que impede a segunda chamada de inserir de novo depois que
 * a primeira já commitou — exatamente o que `FOR UPDATE` força a serializar no MySQL real.
 *
 * Rodar com: php tests/agenda_alerta_pendente_test.php
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

$pdo->exec("CREATE TABLE agenda (
    id INTEGER PRIMARY KEY AUTOINCREMENT, empresa_id INTEGER NOT NULL, usuario_id INTEGER,
    titulo TEXT NOT NULL, data_inicio TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'agendado',
    rrule TEXT, recorrencia_excluida INTEGER DEFAULT 0, ultimo_alerta_pendente_em TEXT
)");
$pdo->exec("CREATE TABLE notificacoes (
    id INTEGER PRIMARY KEY AUTOINCREMENT, empresa_id INTEGER, usuario_id INTEGER, tipo TEXT
)");

function horas_atras(int $n): string { return (new DateTime())->modify("-{$n} hours")->format('Y-m-d H:i:s'); }
function minutos_atras(int $n): string { return (new DateTime())->modify("-{$n} minutes")->format('Y-m-d H:i:s'); }

$pdo->exec("INSERT INTO agenda (id, empresa_id, usuario_id, titulo, data_inicio, status, ultimo_alerta_pendente_em) VALUES
    (1, 10, 5, 'Tio Sérgio, comprar o fluxo de solda', '" . horas_atras(4) . "', 'agendado', NULL)
");

// Mesma condição de enviarAlertasPendentes() (WHERE da versão corrigida), em sintaxe SQLite —
// equivalente lógico de `NOW()`/`INTERVAL 3 HOUR`.
$where = "rrule IS NULL AND status NOT IN ('concluido', 'cancelado') AND usuario_id IS NOT NULL
    AND (recorrencia_excluida = 0 OR recorrencia_excluida IS NULL)
    AND data_inicio <= datetime('now')
    AND (ultimo_alerta_pendente_em IS NULL OR ultimo_alerta_pendente_em <= datetime('now', '-3 hours'))";

function elegiveisSolto(PDO $pdo, string $where): array
{
    return array_column($pdo->query("SELECT id FROM agenda WHERE $where")->fetchAll(), 'id');
}
function recheckELock(PDO $pdo, string $where, int $id): ?array
{
    $st = $pdo->prepare("SELECT id, empresa_id, usuario_id, titulo FROM agenda WHERE id = ? AND $where");
    $st->execute([$id]);
    $row = $st->fetch();
    return $row === false ? null : $row;
}
function inserirAlerta(PDO $pdo, array $ev): void
{
    $pdo->prepare("INSERT INTO notificacoes (empresa_id, usuario_id, tipo) VALUES (?, ?, 'agenda_pendente_confirmacao')")
        ->execute([$ev['empresa_id'], $ev['usuario_id']]);
}
function marcarAlertado(PDO $pdo, int $id): void
{
    $pdo->prepare("UPDATE agenda SET ultimo_alerta_pendente_em = datetime('now') WHERE id = ?")->execute([$id]);
}

// ── 1) Reproduz o BUG: duas "chamadas" (cron + poller web) rodando a versão ANTIGA, sem
//      recheck — as duas leem elegível antes de qualquer uma marcar, as duas inserem. ────────
$idsA = elegiveisSolto($pdo, $where);
$idsB = elegiveisSolto($pdo, $where); // a 2ª "requisição" já rodou o SELECT antes da 1ª terminar
foreach ($idsA as $id) { $ev = recheckELock($pdo, '1=1', $id); inserirAlerta($pdo, $ev); marcarAlertado($pdo, $id); }
foreach ($idsB as $id) { $ev = recheckELock($pdo, '1=1', $id); inserirAlerta($pdo, $ev); marcarAlertado($pdo, $id); } // sem recheck real = duplica

$totalNotifs = (int) $pdo->query("SELECT COUNT(*) c FROM notificacoes")->fetch()['c'];
assert_igual(2, $totalNotifs, 'reproduzido: sem recheck sob lock, a corrida duplica o alerta (bug real do print)');

// reset pra testar a versão corrigida
$pdo->exec("DELETE FROM notificacoes");
$pdo->exec("UPDATE agenda SET ultimo_alerta_pendente_em = NULL WHERE id = 1");

// ── 2) Versão CORRIGIDA: cada "chamada" faz SELECT solto, mas RECHECA a mesma condição por id
//      antes de inserir — a 2ª chamada, processando depois que a 1ª já commitou o UPDATE, não
//      encontra mais o evento elegível no recheck e pula. ─────────────────────────────────────
function processarComoCorrigido(PDO $pdo, string $where, array $ids): int
{
    $enviados = 0;
    foreach ($ids as $id) {
        $ev = recheckELock($pdo, $where, $id); // equivalente ao "SELECT ... FOR UPDATE" real
        if ($ev === null) { continue; } // outro processo já tratou — exatamente o caso do bug
        inserirAlerta($pdo, $ev);
        marcarAlertado($pdo, $id);
        $enviados++;
    }
    return $enviados;
}

$idsA = elegiveisSolto($pdo, $where);
$idsB = elegiveisSolto($pdo, $where); // mesma corrida de antes: a 2ª lista já foi buscada antes da 1ª processar
$enviadosA = processarComoCorrigido($pdo, $where, $idsA);
$enviadosB = processarComoCorrigido($pdo, $where, $idsB);

$totalNotifsCorrigido = (int) $pdo->query("SELECT COUNT(*) c FROM notificacoes")->fetch()['c'];
assert_igual(1, $totalNotifsCorrigido, 'corrigido: mesmo com a mesma corrida, só 1 notificação é inserida');
assert_igual(1, $enviadosA, 'a 1ª chamada processa e envia o alerta');
assert_igual(0, $enviadosB, 'a 2ª chamada (corrida) reconfere, vê que já foi tratado, e pula sem inserir de novo');

// ── 3) Depois do intervalo de 3h passar de verdade, o mesmo evento volta a ser elegível —
//      confirma que a correção não quebrou o reenvio periódico legítimo. ──────────────────────
$pdo->exec("UPDATE agenda SET ultimo_alerta_pendente_em = '" . horas_atras(4) . "' WHERE id = 1");
$idsDepoisDe3h = elegiveisSolto($pdo, $where);
assert_igual([1], $idsDepoisDe3h, 'passadas mais de 3h do último alerta, o evento volta a ser elegível pro reenvio');

// ── 4) Evento concluído nunca deve ser alertado, mesmo que o horário já tenha passado. ────────
$pdo->exec("INSERT INTO agenda (id, empresa_id, usuario_id, titulo, data_inicio, status) VALUES
    (2, 10, 5, 'Já concluído', '" . horas_atras(5) . "', 'concluido')");
$ids = elegiveisSolto($pdo, $where);
assert_igual(false, in_array(2, $ids, true), 'evento já concluído nunca entra, mesmo com horário vencido');

echo "\n" . str_repeat('-', 60) . "\n";
echo $total . " verificações, " . $falhas . " falha(s).\n";
exit($falhas > 0 ? 1 : 0);
