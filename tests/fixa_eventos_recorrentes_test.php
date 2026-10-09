<?php
/*
 * Eventos recorrentes da Agenda do Carteira Fixa (pedido do usuário: "faça o mesmo [de Contas
 * recorrentes] pra editar evento"). Testa App\Services\Fixa\EventoRecorrenteService DE VERDADE
 * (sem reimplementar nada) contra SQLite em memória: clamp do dia do mês, geração idempotente
 * de evento, nunca retroativo, janela data_inicio/data_fim (inclusive geração em lote pra uma
 * recorrência finita, tipo "6 consultas de um tratamento"), pausar/retomar, e que excluir o
 * molde não apaga os eventos já gerados (ON DELETE SET NULL).
 *
 * Rodar com: php tests/fixa_eventos_recorrentes_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Services/Fixa/EventoRecorrenteService.php';

use App\Services\Fixa\EventoRecorrenteService;

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
    $pdo->exec('PRAGMA foreign_keys = ON');

    $pdo->exec("CREATE TABLE usuarios (id INTEGER PRIMARY KEY)");
    $pdo->exec("CREATE TABLE financeiro_pessoal_perfis (id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, tipo TEXT DEFAULT 'pf')");
    $pdo->exec("CREATE TABLE financeiro_pessoal_eventos_recorrentes (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER NOT NULL,
        titulo TEXT NOT NULL, dia_mes INTEGER NOT NULL, hora TEXT DEFAULT '08:00:00',
        data_inicio TEXT, data_fim TEXT, ativo INTEGER DEFAULT 1, criado_em TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE financeiro_pessoal_eventos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER,
        lancamento_id INTEGER, recorrente_id INTEGER, titulo TEXT NOT NULL, data_hora TEXT NOT NULL,
        lido_em TEXT,
        FOREIGN KEY (recorrente_id) REFERENCES financeiro_pessoal_eventos_recorrentes(id) ON DELETE SET NULL
    )");
    return $pdo;
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "== dataOcorrenciaNoMes() — clamp pro último dia real do mês ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    assert_igual('2026-11-05', EventoRecorrenteService::dataOcorrenciaNoMes(5, '2026-11'), 'dia normal, mês com 30 dias');
    assert_igual('2026-02-28', EventoRecorrenteService::dataOcorrenciaNoMes(31, '2026-02'), 'dia 31 clampado pro último dia de fevereiro (2026, não bissexto)');
    assert_igual('2024-02-29', EventoRecorrenteService::dataOcorrenciaNoMes(31, '2024-02'), 'dia 31 clampado pro 29 em fevereiro de ano bissexto');
    assert_igual('2026-11-01', EventoRecorrenteService::dataOcorrenciaNoMes(0, '2026-11'), 'dia 0 (entrada inválida) sobe pro mínimo 1');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== gerarPendentes() — cria evento, mês atual e próximo ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id, tipo) VALUES (10, 1, 'pf')");

    // Dia bem no futuro (28) — garante que, não importa o dia real em que o teste roda, o
    // vencimento do mês atual ainda não passou (mesmo truque já usado em fixa_recorrentes_test).
    $id = EventoRecorrenteService::criar($pdo, 1, 10, [
        'titulo' => 'Pagar fatura', 'dia_mes' => 28, 'hora' => '08:00:00',
        'data_inicio' => null, 'data_fim' => null,
    ]);

    $criados = EventoRecorrenteService::gerarPendentes($pdo, 1, 10);
    assert_igual(2, $criados, 'gera 2 eventos (mês atual + próximo) na primeira vez');

    $evts = $pdo->query("SELECT * FROM financeiro_pessoal_eventos ORDER BY data_hora")->fetchAll();
    assert_igual(2, count($evts), '2 eventos gravados de verdade');
    assert_igual('Pagar fatura', $evts[0]['titulo'], 'título copiado do molde');
    assert_verdadeiro(str_ends_with($evts[0]['data_hora'], '08:00:00'), 'horário do molde aplicado ao evento gerado');
    assert_igual((string) $id, (string) $evts[0]['recorrente_id'], 'evento referencia o molde que o gerou');

    $criadosDeNovo = EventoRecorrenteService::gerarPendentes($pdo, 1, 10);
    assert_igual(0, $criadosDeNovo, 'rodar de novo não duplica nada (idempotente)');
    $totalDepois = (int) $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_eventos")->fetchColumn();
    assert_igual(2, $totalDepois, 'continua só 2 eventos depois de rodar duas vezes');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== gerarPendentes() — nunca gera retroativo pro mês já vencido ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id, tipo) VALUES (10, 1, 'pf')");

    EventoRecorrenteService::criar($pdo, 1, 10, [
        'titulo' => 'Já vencido este mês', 'dia_mes' => 1, 'hora' => '08:00:00',
        'data_inicio' => null, 'data_fim' => null,
    ]);

    $hoje = (int) date('j');
    if ($hoje > 1) {
        $criados = EventoRecorrenteService::gerarPendentes($pdo, 1, 10);
        assert_igual(1, $criados, 'só gera o PRÓXIMO mês — o deste mês (dia 1) já passou, não inventa retroativo');
    } else {
        echo "  (pulado — hoje é dia 1, o cenário 'já vencido este mês' não se aplica)\n";
    }
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== gerarPendentes() — respeita data_inicio/data_fim ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id, tipo) VALUES (10, 1, 'pf')");
    EventoRecorrenteService::criar($pdo, 1, 10, [
        'titulo' => 'Começa daqui a 3 meses', 'dia_mes' => 28, 'hora' => '08:00:00',
        'data_inicio' => date('Y-m-d', strtotime('+3 months')), 'data_fim' => null,
    ]);
    assert_igual(0, EventoRecorrenteService::gerarPendentes($pdo, 1, 10),
        'data_inicio no futuro bloqueia os 2 meses cobertos por esta rodada');

    $pdo2 = novoBanco();
    $pdo2->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo2->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id, tipo) VALUES (10, 1, 'pf')");
    EventoRecorrenteService::criar($pdo2, 1, 10, [
        'titulo' => 'Já terminou mês passado', 'dia_mes' => 28, 'hora' => '08:00:00',
        'data_inicio' => null, 'data_fim' => date('Y-m-d', strtotime('-1 month')),
    ]);
    assert_igual(0, EventoRecorrenteService::gerarPendentes($pdo2, 1, 10),
        'data_fim já vencida bloqueia qualquer geração nova');

    // Tratamento de 6 consultas (janela finita, tipo compra parcelada mas sem dinheiro) — tem
    // que gerar as 6 ocorrências numa chamada só, não só mês atual + próximo. Era exatamente o
    // bug relatado (parcelas/consultas futuras só apareciam no calendário mês a mês).
    $pdo3 = novoBanco();
    $pdo3->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo3->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id, tipo) VALUES (10, 1, 'pf')");
    $dataFim6x = date('Y-m-t', strtotime(date('Y-m-01') . ' +5 months'));
    EventoRecorrenteService::criar($pdo3, 1, 10, [
        'titulo' => 'Consulta do tratamento', 'dia_mes' => 28, 'hora' => '14:30:00',
        'data_inicio' => null, 'data_fim' => $dataFim6x,
    ]);
    $criados6x = EventoRecorrenteService::gerarPendentes($pdo3, 1, 10);
    assert_igual(6, $criados6x, 'janela de 6 meses gera as 6 consultas numa chamada só');
    $totalEvts6x = (int) $pdo3->query("SELECT COUNT(*) FROM financeiro_pessoal_eventos")->fetchColumn();
    assert_igual(6, $totalEvts6x, '6 eventos gravados de verdade, um por mês');
    assert_igual(0, EventoRecorrenteService::gerarPendentes($pdo3, 1, 10), 'rodar de novo não duplica nenhuma das 6 consultas');

    // Teto de segurança — janela absurdamente grande nunca gera mais que 60 numa chamada só.
    $pdo4 = novoBanco();
    $pdo4->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo4->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id, tipo) VALUES (10, 1, 'pf')");
    $dataFimGigante = date('Y-m-t', strtotime(date('Y-m-01') . ' +119 months'));
    EventoRecorrenteService::criar($pdo4, 1, 10, [
        'titulo' => 'Janela gigante', 'dia_mes' => 28, 'hora' => '08:00:00',
        'data_inicio' => null, 'data_fim' => $dataFimGigante,
    ]);
    assert_igual(60, EventoRecorrenteService::gerarPendentes($pdo4, 1, 10),
        'janela de 120 meses é capada em 60');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== gerarPendentes() — ignora recorrência pausada ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id, tipo) VALUES (10, 1, 'pf')");

    $idPausado = EventoRecorrenteService::criar($pdo, 1, 10, [
        'titulo' => 'Pausado', 'dia_mes' => 28, 'hora' => '08:00:00',
        'data_inicio' => null, 'data_fim' => null,
    ]);
    EventoRecorrenteService::alternarAtivo($pdo, $idPausado, 1, 10, false);

    $criados = EventoRecorrenteService::gerarPendentes($pdo, 1, 10);
    assert_igual(0, $criados, 'não gera nada pro pausado');

    EventoRecorrenteService::alternarAtivo($pdo, $idPausado, 1, 10, true);
    $criadosDepoisRetomar = EventoRecorrenteService::gerarPendentes($pdo, 1, 10);
    assert_igual(2, $criadosDepoisRetomar, 'retomar gera os 2 meses normalmente');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== excluir() o molde não apaga os eventos já gerados (ON DELETE SET NULL) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id, tipo) VALUES (10, 1, 'pf')");

    $id = EventoRecorrenteService::criar($pdo, 1, 10, [
        'titulo' => 'Consulta', 'dia_mes' => 28, 'hora' => '08:00:00',
        'data_inicio' => null, 'data_fim' => null,
    ]);
    EventoRecorrenteService::gerarPendentes($pdo, 1, 10);
    $totalAntes = (int) $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_eventos")->fetchColumn();
    assert_igual(2, $totalAntes, 'pré-condição: 2 eventos gerados');

    assert_verdadeiro(EventoRecorrenteService::excluir($pdo, $id, 1, 10), 'excluir() confirma que removeu');

    $totalDepois = (int) $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_eventos")->fetchColumn();
    assert_igual(2, $totalDepois, 'os 2 eventos continuam existindo depois de excluir o molde');
    $recorrenteIdNulo = (int) $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_eventos WHERE recorrente_id IS NULL")->fetchColumn();
    assert_igual(2, $recorrenteIdNulo, 'recorrente_id virou NULL nos dois, não ficou um id fantasma');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Isolamento por usuário/perfil (buscar/atualizar/alternarAtivo/excluir) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO usuarios (id) VALUES (1), (2)");
    $pdo->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id, tipo) VALUES (10, 1, 'pf'), (20, 2, 'pf')");

    $id = EventoRecorrenteService::criar($pdo, 1, 10, [
        'titulo' => 'Consulta', 'dia_mes' => 28, 'hora' => '08:00:00',
        'data_inicio' => null, 'data_fim' => null,
    ]);

    assert_igual(null, EventoRecorrenteService::buscar($pdo, $id, 2, 20), 'usuário 2 não enxerga a recorrência do usuário 1');
    assert_verdadeiro(EventoRecorrenteService::buscar($pdo, $id, 1, 10) !== null, 'dono enxerga normalmente');
    assert_verdadeiro(!EventoRecorrenteService::alternarAtivo($pdo, $id, 2, 20, false), 'usuário 2 não consegue pausar a recorrência do usuário 1');
    assert_verdadeiro(!EventoRecorrenteService::excluir($pdo, $id, 2, 20), 'usuário 2 não consegue excluir a recorrência do usuário 1');

    $existeAinda = $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_eventos_recorrentes WHERE id = $id")->fetchColumn();
    assert_igual('1', (string) $existeAinda, 'recorrência do usuário 1 sobrevive às tentativas do usuário 2');
}

echo "\n------------------------------------------------------------\n";
echo "$total verificações, $falhas falha(s).\n";
exit($falhas > 0 ? 1 : 0);
