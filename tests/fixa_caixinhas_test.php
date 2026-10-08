<?php
/*
 * Caixinhas (reserva de dinheiro pra guardar, ver CLAUDE.md/CaixinhaService) — testa
 * CaixinhaService de ponta a ponta contra SQLite em memória (todo SQL do serviço é
 * parametrizado simples, só `guardadoNoMes()` usa `DATE_FORMAT()` só-MySQL, registrada aqui
 * como função custom do SQLite pra rodar a MESMA query real).
 *
 * Cobre exatamente o pedido do usuário: depositar não mexe em "Gasto no mês" (a tabela
 * `financeiro_pessoal_lancamentos` nem é tocada pelo serviço, então a réplica da agregação de
 * FinanceiroPessoalController::montarResumoMensal() continua em zero por construção, não por
 * um filtro explícito); retirar não pode passar do saldo; excluir exige saldo zerado;
 * transferido_banco nasce pendente e vira 1 só quando marcado.
 *
 * Rodar com: php tests/fixa_caixinhas_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Helpers/functions.php';
require BASE_PATH . '/app/Services/Fixa/CaixinhaService.php';

use App\Services\Fixa\CaixinhaService;

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
    // DATE_FORMAT(data, '%Y-%m') — só usado por CaixinhaService::guardadoNoMes(), o MySQL real
    // tem essa função nativa, o SQLite não.
    $pdo->sqliteCreateFunction('DATE_FORMAT', function (string $data, string $fmt) {
        return $fmt === '%Y-%m' ? substr($data, 0, 7) : $data;
    });

    $pdo->exec("CREATE TABLE usuarios (id INTEGER PRIMARY KEY)");
    $pdo->exec("CREATE TABLE financeiro_pessoal_perfis (id INTEGER PRIMARY KEY, usuario_id INTEGER)");
    $pdo->exec("CREATE TABLE financeiro_pessoal_contas (id INTEGER PRIMARY KEY, perfil_id INTEGER, nome TEXT, saldo_inicial REAL DEFAULT 0)");
    $pdo->exec("CREATE TABLE financeiro_pessoal_lancamentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER, perfil_id INTEGER, conta_id INTEGER,
        tipo TEXT, categoria TEXT DEFAULT 'outros', descricao TEXT, valor REAL, data_hora TEXT,
        vencimento TEXT, pago_em TEXT
    )");
    $pdo->exec("CREATE TABLE caixinhas (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER NOT NULL,
        nome TEXT NOT NULL, cor TEXT DEFAULT '#8C7CFF', icone TEXT DEFAULT 'piggy-bank-fill',
        meta_centavos INTEGER, data_meta TEXT, arquivada INTEGER DEFAULT 0,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE caixinha_movimentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, caixinha_id INTEGER NOT NULL, usuario_id INTEGER NOT NULL,
        perfil_id INTEGER NOT NULL, tipo TEXT NOT NULL, valor_centavos INTEGER NOT NULL, data TEXT NOT NULL,
        conta_id INTEGER, transferido_banco INTEGER DEFAULT 0, observacao TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    return $pdo;
}

/** Réplica mínima da agregação de "Gasto no mês" de FinanceiroPessoalController::
 *  montarResumoMensal() — só pra PROVAR que depositar/retirar numa caixinha não move esse
 *  número (a tabela financeiro_pessoal_lancamentos não é tocada pelo CaixinhaService). */
function gastoDoMes(PDO $db, int $perfilId, string $mes): float
{
    $st = $db->prepare(
        "SELECT COALESCE(SUM(valor), 0) FROM financeiro_pessoal_lancamentos
         WHERE perfil_id = ? AND tipo = 'despesa' AND pago_em IS NOT NULL AND substr(data_hora,1,7) = ?"
    );
    $st->execute([$perfilId, $mes]);
    return (float) $st->fetchColumn();
}
function recebidoDoMes(PDO $db, int $perfilId, string $mes): float
{
    $st = $db->prepare(
        "SELECT COALESCE(SUM(valor), 0) FROM financeiro_pessoal_lancamentos
         WHERE perfil_id = ? AND tipo = 'receita' AND pago_em IS NOT NULL AND substr(data_hora,1,7) = ?"
    );
    $st->execute([$perfilId, $mes]);
    return (float) $st->fetchColumn();
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "== Caixinha nasce com saldo 0 ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id) VALUES (1, 1)");
    $pdo->exec("INSERT INTO caixinhas (id, usuario_id, perfil_id, nome) VALUES (1, 1, 1, 'Viagem')");

    assert_igual(0, CaixinhaService::saldoCaixinha($pdo, 1), 'caixinha recém-criada tem saldo 0');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Depositar: saldo da caixinha sobe, saldo da conta cai, Gasto no mês NÃO muda ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id) VALUES (1, 1)");
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, perfil_id, nome, saldo_inicial) VALUES (10, 1, 'Carteira', 500.00)");
    $pdo->exec("INSERT INTO caixinhas (id, usuario_id, perfil_id, nome) VALUES (1, 1, 1, 'Viagem')");
    $mesAtual = date('Y-m');

    assert_igual(0.0, gastoDoMes($pdo, 1, $mesAtual), 'pré-condição: Gasto no mês começa em 0');

    $movId = CaixinhaService::depositar($pdo, 1, 1, 1, 10000, date('Y-m-d'), 10, 'reserva pra viagem');
    assert_verdadeiro($movId > 0, 'depósito gravado, id retornado');
    assert_igual(10000, CaixinhaService::saldoCaixinha($pdo, 1), 'saldo da caixinha = R$100,00 (10000 centavos)');

    $porConta = CaixinhaService::saldoPorConta($pdo, [10]);
    assert_igual(10000, $porConta[10], 'saldoPorConta() mostra os mesmos 10000 centavos pra conta 10 (é isso que FixaContasController subtrai do saldo da conta)');

    assert_igual(0.0, gastoDoMes($pdo, 1, $mesAtual), 'Gasto no mês CONTINUA 0 depois do depósito — a tabela de lançamentos nem foi tocada');
    assert_igual(0.0, recebidoDoMes($pdo, 1, $mesAtual), 'Recebido no mês também continua 0');

    $totalPerfil = CaixinhaService::saldoTotalCaixinhas($pdo, 1);
    assert_igual(10000, $totalPerfil, 'saldoTotalCaixinhas() do perfil bate com o único depósito feito');

    $guardadoMes = CaixinhaService::guardadoNoMes($pdo, 1, $mesAtual);
    assert_igual(10000, $guardadoMes, 'guardadoNoMes() do mês atual bate com o depósito de hoje');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Retirar: saldo da caixinha cai, saldo da conta volta, Recebido no mês NÃO muda ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id) VALUES (1, 1)");
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, perfil_id, nome) VALUES (10, 1, 'Carteira')");
    $pdo->exec("INSERT INTO caixinhas (id, usuario_id, perfil_id, nome) VALUES (1, 1, 1, 'Viagem')");
    CaixinhaService::depositar($pdo, 1, 1, 1, 10000, date('Y-m-d'), 10, null);
    $mesAtual = date('Y-m');

    $movId = CaixinhaService::retirar($pdo, 1, 1, 1, 3000, date('Y-m-d'), 10, null);
    assert_verdadeiro($movId !== null && $movId > 0, 'retirada de R$30,00 aceita, id retornado');
    assert_igual(7000, CaixinhaService::saldoCaixinha($pdo, 1), 'saldo da caixinha = R$70,00 (10000 - 3000)');

    $porConta = CaixinhaService::saldoPorConta($pdo, [10]);
    assert_igual(7000, $porConta[10], 'líquido na conta volta a refletir só os 70 que ainda estão guardados (depósito 100 - retirada 30)');

    assert_igual(0.0, recebidoDoMes($pdo, 1, $mesAtual), 'Recebido no mês continua 0 — retirada nunca vira "receita" em financeiro_pessoal_lancamentos');
    assert_igual(0.0, gastoDoMes($pdo, 1, $mesAtual), 'Gasto no mês também continua 0');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Retirar mais que o saldo — rejeitado, nada muda ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id) VALUES (1, 1)");
    $pdo->exec("INSERT INTO caixinhas (id, usuario_id, perfil_id, nome) VALUES (1, 1, 1, 'Viagem')");
    CaixinhaService::depositar($pdo, 1, 1, 1, 5000, date('Y-m-d'), null, null);

    $movId = CaixinhaService::retirar($pdo, 1, 1, 1, 5001, date('Y-m-d'), null, null);
    assert_igual(null, $movId, 'retirar R$50,01 contra saldo de R$50,00: rejeitado (null)');
    assert_igual(5000, CaixinhaService::saldoCaixinha($pdo, 1), 'saldo não muda depois da tentativa rejeitada');

    $totalMovs = (int) $pdo->query("SELECT COUNT(*) FROM caixinha_movimentos WHERE caixinha_id = 1")->fetchColumn();
    assert_igual(1, $totalMovs, 'nenhuma linha nova gravada na tentativa rejeitada (só o depósito original)');

    // Retirar exatamente o saldo total é permitido (limite, não "menor que").
    $movIdExato = CaixinhaService::retirar($pdo, 1, 1, 1, 5000, date('Y-m-d'), null, null);
    assert_verdadeiro($movIdExato !== null, 'retirar exatamente o saldo total (não mais que) é aceito');
    assert_igual(0, CaixinhaService::saldoCaixinha($pdo, 1), 'saldo zera depois de retirar tudo');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Excluir exige saldo zerado ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO usuarios (id) VALUES (1)");
    $pdo->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id) VALUES (1, 1)");
    $pdo->exec("INSERT INTO caixinhas (id, usuario_id, perfil_id, nome) VALUES (1, 1, 1, 'Viagem')");
    $pdo->exec("INSERT INTO caixinhas (id, usuario_id, perfil_id, nome) VALUES (2, 1, 1, 'Emergência')");
    CaixinhaService::depositar($pdo, 1, 1, 1, 2000, date('Y-m-d'), null, null);

    assert_verdadeiro(!CaixinhaService::podeExcluir($pdo, 1), 'caixinha com saldo > 0 não pode ser excluída');
    assert_verdadeiro(CaixinhaService::podeExcluir($pdo, 2), 'caixinha sem nenhum movimento (saldo 0) pode ser excluída');

    CaixinhaService::retirar($pdo, 1, 1, 1, 2000, date('Y-m-d'), null, null);
    assert_verdadeiro(CaixinhaService::podeExcluir($pdo, 1), 'depois de retirar tudo (saldo volta a 0), pode excluir');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== transferido_banco — nasce pendente, 'Já transferi' marca, isolado por usuário/perfil ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO usuarios (id) VALUES (1), (2)");
    $pdo->exec("INSERT INTO financeiro_pessoal_perfis (id, usuario_id) VALUES (1, 1), (2, 2)");
    $pdo->exec("INSERT INTO caixinhas (id, usuario_id, perfil_id, nome) VALUES (1, 1, 1, 'Viagem')");
    $movId = CaixinhaService::depositar($pdo, 1, 1, 1, 10000, date('Y-m-d'), null, null);

    $pendente = (int) $pdo->query("SELECT transferido_banco FROM caixinha_movimentos WHERE id = $movId")->fetchColumn();
    assert_igual(0, $pendente, 'depósito nasce com transferido_banco=0 (pendente)');

    $lista = CaixinhaService::listarComSaldo($pdo, 1);
    assert_igual(true, $lista[0]['falta_transferir'], 'listarComSaldo() sinaliza falta_transferir=true enquanto pendente');

    // Usuário 2 (outro perfil) não consegue marcar o movimento do usuário 1 — defesa IDOR.
    $okErrado = CaixinhaService::marcarTransferido($pdo, $movId, 2, 2);
    assert_verdadeiro(!$okErrado, 'usuário 2 tentando marcar o movimento do usuário 1: rejeitado');

    $okCerto = CaixinhaService::marcarTransferido($pdo, $movId, 1, 1);
    assert_verdadeiro($okCerto, 'dono de verdade consegue marcar "Já transferi"');

    $listaDepois = CaixinhaService::listarComSaldo($pdo, 1);
    assert_igual(false, $listaDepois[0]['falta_transferir'], 'falta_transferir vira false depois de marcado');

    // Idempotente — marcar de novo não quebra, só não encontra mais linha pendente pra mudar.
    $okDeNovo = CaixinhaService::marcarTransferido($pdo, $movId, 1, 1);
    assert_verdadeiro(!$okDeNovo, 'marcar de novo um movimento já transferido: rowCount=0, retorna false, sem erro');
}

echo "\n------------------------------------------------------------\n";
echo "$total verificações, $falhas falha(s).\n";
exit($falhas > 0 ? 1 : 0);
