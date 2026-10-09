<?php
/*
 * Contas recorrentes do Carteira Fixa (pedido do usuário: "cadastro de conta recorrente, para
 * aluguel por exemplo, linkando com a agenda e notificando"). Testa
 * App\Services\Fixa\RecorrenteService DE VERDADE (sem reimplementar nada) contra SQLite em
 * memória: clamp do dia do vencimento, geração idempotente de lançamento + evento de agenda,
 * nunca retroativo pro passado, pausar/retomar, e que excluir o molde não apaga os lançamentos
 * já gerados (ON DELETE SET NULL).
 *
 * Rodar com: php tests/fixa_recorrentes_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Helpers/functions.php';
require BASE_PATH . '/app/Services/Fixa/PerfilService.php';
require BASE_PATH . '/app/Services/Fixa/RecorrenteService.php';

use App\Services\Fixa\RecorrenteService;

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
    $pdo->exec("CREATE TABLE financeiro_pessoal_contas (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER NOT NULL,
        nome TEXT NOT NULL, tipo TEXT DEFAULT 'corrente', saldo_inicial REAL DEFAULT 0,
        data_saldo_inicial TEXT, cor TEXT, arquivada INTEGER DEFAULT 0, padrao INTEGER DEFAULT 0
    )");
    $pdo->exec("CREATE TABLE financeiro_pessoal_recorrentes (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER NOT NULL,
        conta_id INTEGER, tipo TEXT DEFAULT 'despesa', categoria TEXT DEFAULT 'outros',
        descricao TEXT NOT NULL, notas TEXT, valor REAL NOT NULL, dia_vencimento INTEGER NOT NULL,
        data_inicio TEXT, data_fim TEXT,
        ativo INTEGER DEFAULT 1, criado_em TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (conta_id) REFERENCES financeiro_pessoal_contas(id) ON DELETE SET NULL
    )");
    $pdo->exec("CREATE TABLE financeiro_pessoal_lancamentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER,
        conta_id INTEGER, recorrente_id INTEGER, tipo TEXT DEFAULT 'despesa', categoria TEXT,
        descricao TEXT NOT NULL, observacao TEXT, valor REAL NOT NULL, data_hora TEXT NOT NULL,
        vencimento TEXT, pago_em TEXT, hora_informada INTEGER DEFAULT 1, origem TEXT DEFAULT 'manual',
        FOREIGN KEY (recorrente_id) REFERENCES financeiro_pessoal_recorrentes(id) ON DELETE SET NULL
    )");
    $pdo->exec("CREATE TABLE financeiro_pessoal_eventos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER,
        lancamento_id INTEGER, titulo TEXT NOT NULL, data_hora TEXT NOT NULL, lido_em TEXT
    )");
    return $pdo;
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "== dataVencimentoNoMes() — clamp pro último dia real do mês ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    assert_igual('2026-11-05', RecorrenteService::dataVencimentoNoMes(5, '2026-11'), 'dia normal, mês com 30 dias');
    // 2026-02-28 é sábado (e 2026-03-01 é domingo) — clamp + rollover pro próximo dia útil.
    assert_igual('2026-03-02', RecorrenteService::dataVencimentoNoMes(31, '2026-02'), 'dia 31 clampado pro último dia de fevereiro (2026, não bissexto) e empurrado pro próximo dia útil (sábado)');
    assert_igual('2024-02-29', RecorrenteService::dataVencimentoNoMes(31, '2024-02'), 'dia 31 clampado pro 29 em fevereiro de ano bissexto (quinta-feira, já é dia útil)');
    assert_igual('2026-04-30', RecorrenteService::dataVencimentoNoMes(31, '2026-04'), 'dia 31 clampado pro 30 em mês de 30 dias (quinta-feira, já é dia útil)');
    // 2026-11-01 é domingo e 2026-11-02 é feriado (Finados) — rollover pula os dois de uma vez.
    assert_igual('2026-11-03', RecorrenteService::dataVencimentoNoMes(0, '2026-11'), 'dia 0 (entrada inválida) sobe pro mínimo 1, depois empurrado por domingo + feriado de Finados');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== dataVencimentoNoMes() — rollover pro próximo dia útil (sábado/domingo/feriado) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    assert_igual(false, fixa_eh_dia_nao_util('2026-11-04'), '2026-11-04 (quarta) é dia útil normal');
    assert_verdadeiro(fixa_eh_dia_nao_util('2026-11-07'), '2026-11-07 (sábado) não é dia útil');
    assert_verdadeiro(fixa_eh_dia_nao_util('2026-11-08'), '2026-11-08 (domingo) não é dia útil');
    assert_verdadeiro(fixa_eh_dia_nao_util('2026-12-25'), '2026-12-25 (Natal, sexta-feira) não é dia útil mesmo sendo dia de semana');
    assert_igual('2026-11-04', fixa_proximo_dia_util('2026-11-04'), 'dia útil não é movido');
    assert_igual('2026-11-09', fixa_proximo_dia_util('2026-11-07'), 'sábado empurra pra segunda (domingo também não serve)');
    assert_igual('2026-12-28', fixa_proximo_dia_util('2026-12-25'), 'Natal (sexta) empurra pro próximo dia útil, pulando o fim de semana seguinte');
    // Dia 25 cai numa quarta-feira comum em 2026-02 — não é feriado nem fim de semana.
    assert_igual('2026-02-25', RecorrenteService::dataVencimentoNoMes(25, '2026-02'), 'dia útil normal não é tocado pelo rollover');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== gerarPendentes() — cria lançamento + evento, mês atual e próximo ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id, tipo) VALUES (10, 1, 'pf')");
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, data_saldo_inicial) VALUES (100, 1, 10, 'Carteira', '2026-01-01')");

    // Dia de vencimento bem no futuro (28) — garante que, não importa o dia real em que o teste
    // rodar, o vencimento do mês atual ainda não passou (evita teste frágil dependente da data).
    $id = RecorrenteService::criar($pdo, 1, 10, [
        'conta_id' => 100, 'tipo' => 'despesa', 'categoria' => 'moradia',
        'descricao' => 'Aluguel', 'notas' => 'Combinado com o síndico', 'valor' => 1500.00, 'dia_vencimento' => 28, 'data_inicio' => null, 'data_fim' => null,
    ]);

    $criados = RecorrenteService::gerarPendentes($pdo, 1, 10);
    assert_igual(2, $criados, 'gera 2 lançamentos (mês atual + próximo) na primeira vez');

    $lancs = $pdo->query("SELECT * FROM financeiro_pessoal_lancamentos ORDER BY vencimento")->fetchAll();
    assert_igual(2, count($lancs), '2 lançamentos gravados de verdade');
    assert_igual('Aluguel', $lancs[0]['descricao'], 'descrição copiada do molde');
    assert_igual('Combinado com o síndico', $lancs[0]['observacao'], 'notas do molde viram observação (mesmo campo de "Notas extras")');
    assert_igual((string) $id, (string) $lancs[0]['recorrente_id'], 'lançamento referencia o molde que o gerou');
    assert_igual('100', (string) $lancs[0]['conta_id'], 'conta copiada do molde');
    assert_igual(0, (int) $lancs[0]['hora_informada'], 'gerado automaticamente não tem hora própria (só data de vencimento)');

    $eventos = $pdo->query("SELECT * FROM financeiro_pessoal_eventos ORDER BY data_hora")->fetchAll();
    assert_igual(2, count($eventos), 'um evento de agenda por lançamento gerado');
    assert_igual((string) $lancs[0]['id'], (string) $eventos[0]['lancamento_id'], 'evento vinculado ao lançamento certo (sino dispara pra essa agenda)');
    assert_verdadeiro(str_contains($eventos[0]['titulo'], 'Aluguel'), 'título do evento cita a descrição');
    assert_verdadeiro(str_ends_with($eventos[0]['data_hora'], '08:00:00'), 'evento marcado pra 08:00 do dia do vencimento');

    $criadosDeNovo = RecorrenteService::gerarPendentes($pdo, 1, 10);
    assert_igual(0, $criadosDeNovo, 'rodar de novo não duplica nada (idempotente)');
    $totalDepois = (int) $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_lancamentos")->fetchColumn();
    assert_igual(2, $totalDepois, 'continua só 2 lançamentos depois de rodar duas vezes');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== gerarPendentes() — nunca gera retroativo pro mês já vencido ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id, tipo) VALUES (10, 1, 'pf')");
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, data_saldo_inicial) VALUES (100, 1, 10, 'Carteira', '2026-01-01')");

    // Dia de vencimento 1 — quase certamente já passou no mês atual, não importa quando o teste
    // roda (só não vale no 1º dia do mês, aceitável pra este teste).
    RecorrenteService::criar($pdo, 1, 10, [
        'conta_id' => null, 'tipo' => 'despesa', 'categoria' => 'outros',
        'descricao' => 'Já vencido este mês', 'notas' => null, 'valor' => 50.00, 'dia_vencimento' => 1, 'data_inicio' => null, 'data_fim' => null,
    ]);

    $hoje = (int) date('j');
    if ($hoje > 1) {
        $criados = RecorrenteService::gerarPendentes($pdo, 1, 10);
        assert_igual(1, $criados, 'só gera o PRÓXIMO mês — o deste mês (dia 1) já passou, não inventa retroativo');
        $venc = $pdo->query("SELECT vencimento FROM financeiro_pessoal_lancamentos")->fetchColumn();
        assert_igual(fixa_proximo_dia_util(date('Y-m', strtotime('+1 month')) . '-01'), $venc, 'único lançamento gerado é o do próximo mês (ajustado pro próximo dia útil, se dia 1 cair em fim de semana/feriado)');
    } else {
        echo "  (pulado — hoje é dia 1, o cenário 'já vencido este mês' não se aplica)\n";
    }
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== gerarPendentes() — respeita data_inicio/data_fim (migration 097) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id, tipo) VALUES (10, 1, 'pf')");
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, data_saldo_inicial) VALUES (100, 1, 10, 'Carteira', '2026-01-01')");

    // Mesmo truque dos testes acima (dia 28 — "quase certamente ainda não passou" no mês atual).
    RecorrenteService::criar($pdo, 1, 10, [
        'conta_id' => null, 'tipo' => 'despesa', 'categoria' => 'outros',
        'descricao' => 'Começa daqui a 3 meses', 'notas' => null, 'valor' => 10.00, 'dia_vencimento' => 28,
        'data_inicio' => date('Y-m-d', strtotime('+3 months')), 'data_fim' => null,
    ]);
    assert_igual(0, RecorrenteService::gerarPendentes($pdo, 1, 10),
        'data_inicio no futuro bloqueia os 2 meses cobertos por esta rodada (mês atual + próximo)');

    $pdo2 = novoBanco();
    $pdo2->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo2->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id, tipo) VALUES (10, 1, 'pf')");
    $pdo2->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, data_saldo_inicial) VALUES (100, 1, 10, 'Carteira', '2026-01-01')");
    RecorrenteService::criar($pdo2, 1, 10, [
        'conta_id' => null, 'tipo' => 'despesa', 'categoria' => 'outros',
        'descricao' => 'Já terminou mês passado', 'notas' => null, 'valor' => 10.00, 'dia_vencimento' => 28,
        'data_inicio' => null, 'data_fim' => date('Y-m-d', strtotime('-1 month')),
    ]);
    assert_igual(0, RecorrenteService::gerarPendentes($pdo2, 1, 10),
        'data_fim já vencida (mês passado) bloqueia qualquer geração nova, mesmo mês+próximo ainda não vencidos');

    $pdo3 = novoBanco();
    $pdo3->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo3->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id, tipo) VALUES (10, 1, 'pf')");
    $pdo3->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, data_saldo_inicial) VALUES (100, 1, 10, 'Carteira', '2026-01-01')");
    RecorrenteService::criar($pdo3, 1, 10, [
        'conta_id' => null, 'tipo' => 'despesa', 'categoria' => 'outros',
        'descricao' => 'Termina este mês', 'notas' => null, 'valor' => 10.00, 'dia_vencimento' => 28,
        'data_inicio' => null, 'data_fim' => date('Y-m-t'), // último dia do mês atual
    ]);
    $criadosComFim = RecorrenteService::gerarPendentes($pdo3, 1, 10);
    assert_igual(1, $criadosComFim, 'data_fim no fim deste mês libera só a ocorrência deste mês, não a do próximo');
    $vencGerado = $pdo3->query("SELECT vencimento FROM financeiro_pessoal_lancamentos")->fetchColumn();
    assert_igual(RecorrenteService::dataVencimentoNoMes(28, date('Y-m')), $vencGerado,
        'o único lançamento gerado é mesmo o vencimento deste mês, não o do próximo');

    // Compra parcelada em 12x (pedido do usuário: "select de meses em fim... serve pra compra
    // parcelada no cartão de crédito") — janela de 12 meses tem que gerar as 12 ocorrências JÁ
    // NA PRIMEIRA chamada, não só mês atual + próximo (era exatamente o bug relatado: parcelas
    // futuras não apareciam no calendário sem visitar o módulo mês a mês).
    $pdo5 = novoBanco();
    $pdo5->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo5->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id, tipo) VALUES (10, 1, 'pf')");
    $pdo5->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, data_saldo_inicial) VALUES (100, 1, 10, 'Carteira', '2026-01-01')");
    $dataFim12x = date('Y-m-t', strtotime(date('Y-m-01') . ' +11 months'));
    RecorrenteService::criar($pdo5, 1, 10, [
        'conta_id' => null, 'tipo' => 'despesa', 'categoria' => 'outros',
        'descricao' => 'Compra parcelada 12x', 'notas' => null, 'valor' => 150.00, 'dia_vencimento' => 28,
        'data_inicio' => null, 'data_fim' => $dataFim12x,
    ]);
    $criados12x = RecorrenteService::gerarPendentes($pdo5, 1, 10);
    assert_igual(12, $criados12x, 'janela de 12 meses gera as 12 parcelas numa chamada só (não só mês atual + próximo)');
    $totalLancs12x = (int) $pdo5->query("SELECT COUNT(*) FROM financeiro_pessoal_lancamentos")->fetchColumn();
    assert_igual(12, $totalLancs12x, '12 lançamentos gravados de verdade, um por mês');
    $criadosDeNovo12x = RecorrenteService::gerarPendentes($pdo5, 1, 10);
    assert_igual(0, $criadosDeNovo12x, 'rodar de novo não duplica nenhuma das 12 parcelas (idempotente mesmo em lote)');

    // Teto de segurança (MESES_GERACAO_MAX) — uma janela absurdamente grande nunca gera mais que
    // 60 lançamentos numa chamada só, mesmo que a diferença de meses peça mais que isso.
    $pdo6 = novoBanco();
    $pdo6->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo6->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id, tipo) VALUES (10, 1, 'pf')");
    $pdo6->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, data_saldo_inicial) VALUES (100, 1, 10, 'Carteira', '2026-01-01')");
    $dataFimGigante = date('Y-m-t', strtotime(date('Y-m-01') . ' +119 months')); // 10 anos
    RecorrenteService::criar($pdo6, 1, 10, [
        'conta_id' => null, 'tipo' => 'despesa', 'categoria' => 'outros',
        'descricao' => 'Janela gigante', 'notas' => null, 'valor' => 10.00, 'dia_vencimento' => 28,
        'data_inicio' => null, 'data_fim' => $dataFimGigante,
    ]);
    $criadosCap = RecorrenteService::gerarPendentes($pdo6, 1, 10);
    assert_igual(60, $criadosCap, 'janela de 120 meses é capada em 60 — defesa contra rajada de INSERT mesmo com data_fim mal configurada');

    // atualizar() grava e relê o novo período corretamente.
    $pdo4 = novoBanco();
    $pdo4->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo4->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id, tipo) VALUES (10, 1, 'pf')");
    $idUpd = RecorrenteService::criar($pdo4, 1, 10, [
        'conta_id' => null, 'tipo' => 'despesa', 'categoria' => 'outros',
        'descricao' => 'Assinatura', 'notas' => null, 'valor' => 29.90, 'dia_vencimento' => 10,
        'data_inicio' => '2026-01-01', 'data_fim' => null,
    ]);
    RecorrenteService::atualizar($pdo4, $idUpd, 1, 10, [
        'conta_id' => null, 'tipo' => 'despesa', 'categoria' => 'outros',
        'descricao' => 'Assinatura', 'notas' => null, 'valor' => 29.90, 'dia_vencimento' => 10,
        'data_inicio' => '2026-02-01', 'data_fim' => '2026-12-31',
    ]);
    $rAtualizado = RecorrenteService::buscar($pdo4, $idUpd, 1, 10);
    assert_igual('2026-02-01', $rAtualizado['data_inicio'], 'atualizar() grava o novo data_inicio');
    assert_igual('2026-12-31', $rAtualizado['data_fim'], 'atualizar() grava o novo data_fim');

    // atualizarPeriodo() — atalho usado pelo bloco "Esta conta é recorrente" dentro do modal de
    // editar um lançamento gerado por ela (lancamentos.php): mexe SÓ em data_inicio/data_fim,
    // sem precisar reenviar tipo/categoria/valor/dia_vencimento (que esse contexto não tem).
    $pdo7 = novoBanco();
    $pdo7->exec("INSERT INTO usuarios (id) VALUES (1), (2)");
    $pdo7->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id, tipo) VALUES (10, 1, 'pf'), (20, 2, 'pf')");
    $idPeriodo = RecorrenteService::criar($pdo7, 1, 10, [
        'conta_id' => null, 'tipo' => 'despesa', 'categoria' => 'moradia',
        'descricao' => 'Compra parcelada', 'notas' => null, 'valor' => 1200.00, 'dia_vencimento' => 18,
        'data_inicio' => '2026-10-08', 'data_fim' => '2027-09-30',
    ]);
    assert_verdadeiro(
        RecorrenteService::atualizarPeriodo($pdo7, $idPeriodo, 1, 10, '2026-11-01', '2027-04-30'),
        'atualizarPeriodo() confirma a gravação'
    );
    $rPeriodo = RecorrenteService::buscar($pdo7, $idPeriodo, 1, 10);
    assert_igual('2026-11-01', $rPeriodo['data_inicio'], 'atualizarPeriodo() mudou data_inicio');
    assert_igual('2027-04-30', $rPeriodo['data_fim'], 'atualizarPeriodo() mudou data_fim');
    assert_igual('Compra parcelada', $rPeriodo['descricao'], 'descrição/valor/dia_vencimento NÃO mudam — atualizarPeriodo() só mexe no período');
    assert_igual('1200', (string) (int) $rPeriodo['valor'], 'valor preservado');
    assert_igual(18, (int) $rPeriodo['dia_vencimento'], 'dia_vencimento preservado');
    assert_verdadeiro(
        !RecorrenteService::atualizarPeriodo($pdo7, $idPeriodo, 2, 20, '2026-01-01', null),
        'usuário 2 não consegue mudar o período da recorrência do usuário 1'
    );
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== gerarPendentes() — ignora recorrência pausada; sem conta cai na primeira do perfil ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id, tipo) VALUES (10, 1, 'pf')");
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, data_saldo_inicial) VALUES (100, 1, 10, 'Carteira', '2026-01-01')");

    $idPausada = RecorrenteService::criar($pdo, 1, 10, [
        'conta_id' => null, 'tipo' => 'despesa', 'categoria' => 'outros',
        'descricao' => 'Pausada', 'notas' => null, 'valor' => 10.00, 'dia_vencimento' => 28, 'data_inicio' => null, 'data_fim' => null,
    ]);
    RecorrenteService::alternarAtivo($pdo, $idPausada, 1, 10, false);

    $idSemConta = RecorrenteService::criar($pdo, 1, 10, [
        'conta_id' => null, 'tipo' => 'despesa', 'categoria' => 'outros',
        'descricao' => 'Sem conta escolhida', 'notas' => null, 'valor' => 20.00, 'dia_vencimento' => 28, 'data_inicio' => null, 'data_fim' => null,
    ]);

    $criados = RecorrenteService::gerarPendentes($pdo, 1, 10);
    assert_igual(2, $criados, 'só gera pra ativa (2 meses), ignora a pausada por completo');

    $lanc = $pdo->query("SELECT conta_id FROM financeiro_pessoal_lancamentos LIMIT 1")->fetch();
    assert_igual('100', (string) $lanc['conta_id'], 'sem conta no molde, cai na primeira conta do perfil (mesma regra do lançamento manual)');

    // Retomar a pausada passa a gerar a partir de agora.
    RecorrenteService::alternarAtivo($pdo, $idPausada, 1, 10, true);
    $criadosDepoisRetomar = RecorrenteService::gerarPendentes($pdo, 1, 10);
    assert_igual(2, $criadosDepoisRetomar, 'retomar a pausada gera os 2 meses dela, sem mexer na que já existia');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== excluir() o molde não apaga os lançamentos já gerados (ON DELETE SET NULL) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id, tipo) VALUES (10, 1, 'pf')");
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, data_saldo_inicial) VALUES (100, 1, 10, 'Carteira', '2026-01-01')");

    $id = RecorrenteService::criar($pdo, 1, 10, [
        'conta_id' => 100, 'tipo' => 'despesa', 'categoria' => 'moradia',
        'descricao' => 'Aluguel', 'notas' => null, 'valor' => 1500.00, 'dia_vencimento' => 28, 'data_inicio' => null, 'data_fim' => null,
    ]);
    RecorrenteService::gerarPendentes($pdo, 1, 10);
    $totalAntes = (int) $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_lancamentos")->fetchColumn();
    assert_igual(2, $totalAntes, 'pré-condição: 2 lançamentos gerados');

    assert_verdadeiro(RecorrenteService::excluir($pdo, $id, 1, 10), 'excluir() confirma que removeu');

    $totalDepois = (int) $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_lancamentos")->fetchColumn();
    assert_igual(2, $totalDepois, 'os 2 lançamentos continuam existindo depois de excluir o molde');
    $recorrenteIdNulo = (int) $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_lancamentos WHERE recorrente_id IS NULL")->fetchColumn();
    assert_igual(2, $recorrenteIdNulo, 'recorrente_id virou NULL nos dois (ON DELETE SET NULL), não ficou um id fantasma');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Isolamento por usuário/perfil (buscar/atualizar/alternarAtivo/excluir) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO usuarios (id) VALUES (1), (2)");
    $pdo->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id, tipo) VALUES (10, 1, 'pf'), (20, 2, 'pf')");
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, data_saldo_inicial) VALUES (100, 1, 10, 'Carteira', '2026-01-01')");

    $id = RecorrenteService::criar($pdo, 1, 10, [
        'conta_id' => 100, 'tipo' => 'despesa', 'categoria' => 'moradia',
        'descricao' => 'Aluguel', 'notas' => null, 'valor' => 1500.00, 'dia_vencimento' => 28, 'data_inicio' => null, 'data_fim' => null,
    ]);

    assert_igual(null, RecorrenteService::buscar($pdo, $id, 2, 20), 'usuário 2 não enxerga a recorrência do usuário 1');
    assert_verdadeiro(RecorrenteService::buscar($pdo, $id, 1, 10) !== null, 'dono enxerga normalmente');

    assert_verdadeiro(!RecorrenteService::alternarAtivo($pdo, $id, 2, 20, false), 'usuário 2 não consegue pausar a recorrência do usuário 1');
    assert_verdadeiro(!RecorrenteService::excluir($pdo, $id, 2, 20), 'usuário 2 não consegue excluir a recorrência do usuário 1');

    $existeAinda = $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_recorrentes WHERE id = $id")->fetchColumn();
    assert_igual('1', (string) $existeAinda, 'recorrência do usuário 1 sobrevive às tentativas do usuário 2');
}

echo "\n------------------------------------------------------------\n";
echo "$total verificações, $falhas falha(s).\n";
exit($falhas > 0 ? 1 : 0);
