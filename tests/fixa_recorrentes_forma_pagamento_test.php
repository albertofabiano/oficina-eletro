<?php
/*
 * Contas recorrentes + forma de pagamento (ROTEIRO-carteira-fixa.md) — testa
 * App\Services\Fixa\RecorrenteService::gerarPendentes() DE VERDADE (sem reimplementar) contra
 * SQLite em memória: quando a recorrência tem uma forma ligada (ex.: cartão que fecha a fatura
 * antes de cobrar), o lançamento mensal gerado passa a ter `vencimento` = data EFETIVA calculada
 * pela forma (App\Services\Fixa\FormaPagamentoService::resolverParcelas()), `data_evento` = o
 * dia "cru" configurado na recorrência (dia_vencimento), e herda a conta da FORMA quando ela tem
 * uma própria — sem forma nenhuma, tudo continua exatamente como sempre foi (vencimento = dia
 * cru, data_evento fica NULL).
 *
 * Não hardcoda datas fixas (evita teste frágil dependente de quando ele rodar) — em vez disso,
 * confere que o `vencimento` gerado bate com o que FormaPagamentoService::resolverParcelas()
 * calcularia pra aquele mesmo dia/forma, e que `data_evento` cai no dia configurado.
 *
 * Rodar com: php tests/fixa_recorrentes_forma_pagamento_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Helpers/functions.php';
require BASE_PATH . '/app/Services/Fixa/PerfilService.php';
require BASE_PATH . '/app/Services/Fixa/FormaPagamentoService.php';
require BASE_PATH . '/app/Services/Fixa/RecorrenteService.php';

use App\Services\Fixa\RecorrenteService;
use App\Services\Fixa\FormaPagamentoService;

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
    $pdo->exec("CREATE TABLE financeiro_pessoal_formas_pagamento (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER NOT NULL,
        conta_id INTEGER, nome TEXT NOT NULL, tipo TEXT DEFAULT 'simples', direcao TEXT DEFAULT 'ambas',
        dia_fechamento INTEGER, dia_vencimento INTEGER, prazo_dias INTEGER DEFAULT 30,
        arquivada INTEGER DEFAULT 0
    )");
    $pdo->exec("CREATE TABLE financeiro_pessoal_recorrentes (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER NOT NULL,
        conta_id INTEGER, forma_pagamento_id INTEGER, tipo TEXT DEFAULT 'despesa', categoria TEXT DEFAULT 'outros',
        descricao TEXT NOT NULL, notas TEXT, valor REAL NOT NULL, dia_vencimento INTEGER NOT NULL,
        data_inicio TEXT, data_fim TEXT,
        ativo INTEGER DEFAULT 1, criado_em TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (conta_id) REFERENCES financeiro_pessoal_contas(id) ON DELETE SET NULL
    )");
    $pdo->exec("CREATE TABLE financeiro_pessoal_lancamentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER,
        conta_id INTEGER, forma_pagamento_id INTEGER, recorrente_id INTEGER, tipo TEXT DEFAULT 'despesa', categoria TEXT,
        descricao TEXT NOT NULL, observacao TEXT, valor REAL NOT NULL, data_hora TEXT NOT NULL,
        data_evento TEXT, vencimento TEXT, pago_em TEXT, hora_informada INTEGER DEFAULT 1, origem TEXT DEFAULT 'manual',
        FOREIGN KEY (recorrente_id) REFERENCES financeiro_pessoal_recorrentes(id) ON DELETE SET NULL
    )");
    $pdo->exec("CREATE TABLE financeiro_pessoal_eventos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER,
        lancamento_id INTEGER, titulo TEXT NOT NULL, data_hora TEXT NOT NULL, lido_em TEXT
    )");
    $pdo->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id, tipo) VALUES (10, 1, 'pf')");
    return $pdo;
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "== gerarPendentes() COM forma de pagamento (cartão) — vencimento vem da fatura ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, data_saldo_inicial) VALUES (100, 1, 10, 'Carteira', '2026-01-01')");
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, data_saldo_inicial) VALUES (200, 1, 10, 'Conta que paga a fatura', '2026-01-01')");
    $pdo->exec(
        "INSERT INTO financeiro_pessoal_formas_pagamento (id, usuario_id, perfil_id, nome, tipo, conta_id, dia_fechamento, dia_vencimento)
         VALUES (900, 1, 10, 'Nubank roxinho', 'cartao', 200, 28, 5)"
    );

    $idRec = RecorrenteService::criar($pdo, 1, 10, [
        'conta_id' => 100, 'forma_pagamento_id' => 900, 'tipo' => 'despesa', 'categoria' => 'assinaturas',
        'descricao' => 'Netflix', 'notas' => null, 'valor' => 55.90, 'dia_vencimento' => 10,
        'data_inicio' => null, 'data_fim' => null,
    ]);

    $criados = RecorrenteService::gerarPendentes($pdo, 1, 10);
    assert_verdadeiro($criados >= 1, 'gerou pelo menos 1 lançamento (mês atual e/ou próximo)');

    $lanc = $pdo->query("SELECT * FROM financeiro_pessoal_lancamentos ORDER BY data_evento LIMIT 1")->fetch();
    assert_igual('900', (string) $lanc['forma_pagamento_id'], 'forma gravada no lançamento gerado');
    assert_igual('200', (string) $lanc['conta_id'], 'conta herdada da FORMA (não da recorrência), que tem conta própria');
    assert_verdadeiro($lanc['data_evento'] !== null, 'data_evento gravada (dia "cru" da cobrança, cartão fecha a fatura antes)');
    // >= 10 (nunca < 10): dataVencimentoNoMes() só empurra pra FRENTE quando o dia 10 cai em
    // fim de semana/feriado (fixa_proximo_dia_util()) — nunca volta, nunca pula pro mês errado
    // (dia 10 está longe o bastante da virada de mês pra isso nunca acontecer).
    assert_verdadeiro((int) substr($lanc['data_evento'], 8, 2) >= 10, 'data_evento cai no dia 10 configurado (ou no próximo dia útil, se 10 caiu num fim de semana/feriado)');
    assert_igual('05', substr($lanc['vencimento'], 8, 2), 'vencimento EFETIVO cai no dia 5 (dia_vencimento da fatura do cartão)');
    assert_verdadeiro($lanc['vencimento'] !== $lanc['data_evento'], 'vencimento (quando o dinheiro sai de verdade) é diferente da data do evento');

    // Confere que a data bate exatamente com o que o motor de verdade calcularia pra aquele
    // mesmo dia — não hardcoda a data esperada (evita teste frágil dependente de "hoje").
    $forma = ['tipo' => 'cartao', 'dia_fechamento' => 28, 'dia_vencimento' => 5];
    $esperado = FormaPagamentoService::resolverParcelas($forma, $lanc['data_evento'], 55.90, 1)[0]['data'];
    assert_igual($esperado, $lanc['vencimento'], 'vencimento gravado bate exatamente com FormaPagamentoService::resolverParcelas()');

    $criadosDeNovo = RecorrenteService::gerarPendentes($pdo, 1, 10);
    assert_igual(0, $criadosDeNovo, 'rodar de novo não duplica (idempotente também com forma — checagem por data_evento)');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== gerarPendentes() — forma arquivada DEPOIS de já ligada continua sendo aplicada ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, data_saldo_inicial) VALUES (100, 1, 10, 'Carteira', '2026-01-01')");
    $pdo->exec(
        "INSERT INTO financeiro_pessoal_formas_pagamento (id, usuario_id, perfil_id, nome, tipo, dia_fechamento, dia_vencimento, arquivada)
         VALUES (901, 1, 10, 'Cartão antigo', 'cartao', 20, 1, 1)" // já nasce arquivada
    );
    $idRec = RecorrenteService::criar($pdo, 1, 10, [
        'conta_id' => 100, 'forma_pagamento_id' => 901, 'tipo' => 'despesa', 'categoria' => 'assinaturas',
        'descricao' => 'Academia', 'notas' => null, 'valor' => 120.00, 'dia_vencimento' => 15,
        'data_inicio' => null, 'data_fim' => null,
    ]);

    $criados = RecorrenteService::gerarPendentes($pdo, 1, 10);
    assert_verdadeiro($criados >= 1, 'gera normalmente mesmo com a forma já arquivada');
    $lanc = $pdo->query("SELECT * FROM financeiro_pessoal_lancamentos ORDER BY data_evento LIMIT 1")->fetch();
    assert_igual('901', (string) $lanc['forma_pagamento_id'], 'forma arquivada continua sendo aplicada (arquivar só esconde da lista de ESCOLHA, não desliga quem já usa)');
    assert_verdadeiro($lanc['vencimento'] !== $lanc['data_evento'], 'cálculo da forma (cartão) continua valendo, mesmo arquivada');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== gerarPendentes() SEM forma nenhuma — comportamento idêntico ao de sempre (regressão) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, data_saldo_inicial) VALUES (100, 1, 10, 'Carteira', '2026-01-01')");
    RecorrenteService::criar($pdo, 1, 10, [
        'conta_id' => 100, 'forma_pagamento_id' => null, 'tipo' => 'despesa', 'categoria' => 'moradia',
        'descricao' => 'Aluguel', 'notas' => null, 'valor' => 1500.00, 'dia_vencimento' => 28,
        'data_inicio' => null, 'data_fim' => null,
    ]);
    RecorrenteService::gerarPendentes($pdo, 1, 10);
    $lanc = $pdo->query("SELECT * FROM financeiro_pessoal_lancamentos ORDER BY vencimento LIMIT 1")->fetch();
    assert_igual(null, $lanc['forma_pagamento_id'], 'sem forma, forma_pagamento_id fica NULL');
    assert_igual(null, $lanc['data_evento'], 'sem forma, data_evento fica NULL (vencimento sozinho já representa tudo, como sempre foi)');
    assert_igual('100', (string) $lanc['conta_id'], 'conta continua vindo da recorrência (sem forma pra sobrepor)');
}

echo "\n------------------------------------------------------------\n";
echo "$total verificações, $falhas falha(s).\n";
exit($falhas > 0 ? 1 : 0);
