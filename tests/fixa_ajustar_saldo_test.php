<?php
/*
 * "Corrigir saldo" da conta (pedido do usuário: "deixe editar valores e tudo mais", esclarecido
 * via pergunta — queria ajustar o saldo ATUAL direto, em vez de mexer em "saldo inicial"). A
 * diferença entre o saldo calculado e o valor informado vira um lançamento de "Ajuste de saldo"
 * (já pago, hoje) — saldo_inicial/data_saldo_inicial (editáveis em atualizar()) nunca são
 * reescritos por este caminho.
 *
 * Testa CaixinhaService::saldoPorConta() DE VERDADE (sem reimplementar). O resto da lógica de
 * FixaContasController::ajustarSaldo() é replicado aqui (mesma fórmula exata), porque o
 * controller monta `$this->perfil`/`$this->db` via App\Core\DB::pdo() direto no construtor,
 * sem injeção de PDO de teste possível — mesma limitação já documentada em outros testes deste
 * projeto (ver tests/fixa_conta_padrao_test.php, tests/fixa_notificacao_lancamento_test.php).
 *
 * Rodar com: php tests/fixa_ajustar_saldo_test.php
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

    $pdo->exec("CREATE TABLE financeiro_pessoal_contas (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER NOT NULL,
        nome TEXT NOT NULL, saldo_inicial REAL NOT NULL DEFAULT 0
    )");
    $pdo->exec("CREATE TABLE financeiro_pessoal_lancamentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER,
        conta_id INTEGER, tipo TEXT NOT NULL, categoria TEXT NOT NULL, descricao TEXT NOT NULL,
        valor REAL NOT NULL, data_hora TEXT NOT NULL, vencimento TEXT, pago_em TEXT,
        hora_informada INTEGER NOT NULL DEFAULT 1, origem TEXT NOT NULL DEFAULT 'manual'
    )");
    $pdo->exec("CREATE TABLE financeiro_pessoal_categorias (
        id INTEGER PRIMARY KEY AUTOINCREMENT, perfil_id INTEGER NOT NULL, chave TEXT NOT NULL,
        nome TEXT NOT NULL, cor TEXT NOT NULL, tipo TEXT NOT NULL, icone TEXT, ativo INTEGER NOT NULL DEFAULT 1,
        posicao INTEGER NOT NULL DEFAULT 0
    )");
    $pdo->exec("CREATE TABLE caixinha_movimentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, conta_id INTEGER, tipo TEXT NOT NULL, valor_centavos INTEGER NOT NULL
    )");
    return $pdo;
}

/** Réplica de FixaContasController::saldosPorConta(), igual outros testes já fazem pra métodos
 *  privados equivalentes. */
function saldosPorConta(PDO $db, array $contaIds): array
{
    if (!$contaIds) return [];
    $ph = implode(',', array_fill(0, count($contaIds), '?'));
    $st = $db->prepare(
        "SELECT conta_id,
                SUM(CASE WHEN tipo = 'receita' AND pago_em IS NOT NULL THEN valor ELSE 0 END) receitas_pagas,
                SUM(CASE WHEN tipo = 'despesa' AND pago_em IS NOT NULL THEN valor ELSE 0 END) despesas_pagas
         FROM financeiro_pessoal_lancamentos WHERE conta_id IN ($ph) GROUP BY conta_id"
    );
    $st->execute($contaIds);
    $out = [];
    foreach ($st->fetchAll() as $r) {
        $out[(int) $r['conta_id']] = ['receitas_pagas' => (float) $r['receitas_pagas'], 'despesas_pagas' => (float) $r['despesas_pagas']];
    }
    return $out;
}

/** Réplica de FixaContasController::categoriaParaAjuste(). */
function categoriaParaAjuste(PDO $db, int $perfilId, string $tipoLancamento): string
{
    $st = $db->prepare("SELECT chave, tipo FROM financeiro_pessoal_categorias WHERE perfil_id = ? AND ativo = 1 ORDER BY posicao, id");
    $st->execute([$perfilId]);
    $primeira = null;
    foreach ($st->fetchAll() as $c) {
        if ($primeira === null) { $primeira = $c['chave']; }
        if ($c['tipo'] === $tipoLancamento) { return $c['chave']; }
    }
    return $primeira ?? 'outros';
}

/** Réplica EXATA de FixaContasController::ajustarSaldo() — mesma fórmula, mesmo guard de
 *  no-op quando a diferença é desprezível, mesmo INSERT. Retorna o id do lançamento criado, ou
 *  null se nada foi criado (saldo já estava certo). */
function ajustarSaldo(PDO $db, int $uid, int $perfilId, int $contaId, string $novoSaldoStr): ?int
{
    $st = $db->prepare("SELECT id, saldo_inicial FROM financeiro_pessoal_contas WHERE id = ? AND perfil_id = ?");
    $st->execute([$contaId, $perfilId]);
    $conta = $st->fetch();
    if (!$conta) { return null; }

    $novoSaldo = moeda_float($novoSaldoStr);

    $somas = saldosPorConta($db, [$contaId]);
    $s = $somas[$contaId] ?? ['receitas_pagas' => 0.0, 'despesas_pagas' => 0.0];
    $saldoAtual = fixa_saldo_atual((float) $conta['saldo_inicial'], $s['receitas_pagas'], $s['despesas_pagas']);
    $caixinhaPorConta = CaixinhaService::saldoPorConta($db, [$contaId]);
    $saldoAtual -= (($caixinhaPorConta[$contaId] ?? 0) / 100);
    $saldoAtual = round($saldoAtual, 2);

    $diferenca = round($novoSaldo - $saldoAtual, 2);
    if (abs($diferenca) < 0.005) { return null; }

    $tipoLanc = $diferenca > 0 ? 'receita' : 'despesa';
    $categoria = categoriaParaAjuste($db, $perfilId, $tipoLanc);
    $hoje = date('Y-m-d');

    $db->prepare(
        "INSERT INTO financeiro_pessoal_lancamentos
            (usuario_id, perfil_id, conta_id, tipo, categoria, descricao, valor,
             data_hora, vencimento, pago_em, hora_informada, origem)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'manual')"
    )->execute([$uid, $perfilId, $contaId, $tipoLanc, $categoria, 'Ajuste de saldo', abs($diferenca), $hoje . ' 00:00:00', $hoje, $hoje]);

    return (int) $db->lastInsertId();
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "== Saldo maior que o atual → cria lançamento de RECEITA pela diferença ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, saldo_inicial) VALUES (1, 1, 10, 'Carteira', 0)");
    $pdo->exec("INSERT INTO financeiro_pessoal_categorias (perfil_id, chave, nome, cor, tipo, posicao) VALUES
        (10, 'moradia', 'Moradia', '#000', 'despesa', 0),
        (10, 'outros', 'Outros', '#000', 'despesa', 1),
        (10, 'outras_receitas', 'Outras receitas', '#000', 'receita', 2)");

    // Saldo atual = 0 (sem lançamento nenhum ainda). Corrige pra R$ 2.590,00.
    $id = ajustarSaldo($pdo, 1, 10, 1, '2590');
    assert_verdadeiro($id !== null, 'cria o lançamento de ajuste');

    $lanc = $pdo->query("SELECT * FROM financeiro_pessoal_lancamentos WHERE id = $id")->fetch();
    assert_igual('receita', $lanc['tipo'], 'diferença positiva vira receita');
    assert_igual('2590', (string) (int) $lanc['valor'], 'valor do lançamento é a diferença inteira (2590 - 0)');
    assert_igual('outras_receitas', $lanc['categoria'], 'categoria vem da primeira categoria do perfil com tipo=receita');
    assert_igual('Ajuste de saldo', $lanc['descricao'], 'descrição fixa "Ajuste de saldo"');
    assert_igual(date('Y-m-d'), $lanc['pago_em'], 'nasce já pago hoje (não fica pendente)');
    assert_igual(0, (int) $lanc['hora_informada'], 'hora_informada=0 (gerado automático, não digitado)');
    assert_igual(1, (int) $lanc['conta_id'], 'lançamento vinculado à conta certa');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Saldo menor que o atual → cria lançamento de DESPESA pela diferença ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, saldo_inicial) VALUES (1, 1, 10, 'Carteira', 1000)");
    $pdo->exec("INSERT INTO financeiro_pessoal_categorias (perfil_id, chave, nome, cor, tipo, posicao) VALUES
        (10, 'moradia', 'Moradia', '#000', 'despesa', 0),
        (10, 'salario', 'Salário', '#000', 'receita', 1)");

    // Saldo atual = 1000 (saldo_inicial, sem lançamento nenhum). Corrige pra R$ 300,00.
    $id = ajustarSaldo($pdo, 1, 10, 1, '300');
    assert_verdadeiro($id !== null, 'cria o lançamento de ajuste');

    $lanc = $pdo->query("SELECT * FROM financeiro_pessoal_lancamentos WHERE id = $id")->fetch();
    assert_igual('despesa', $lanc['tipo'], 'diferença negativa vira despesa');
    assert_igual('700', (string) (int) $lanc['valor'], 'valor é o módulo da diferença (1000 - 300 = 700)');
    assert_igual('moradia', $lanc['categoria'], 'categoria vem da primeira categoria do perfil com tipo=despesa');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Saldo informado igual ao atual → não cria lançamento nenhum ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, saldo_inicial) VALUES (1, 1, 10, 'Carteira', 500)");
    $pdo->exec("INSERT INTO financeiro_pessoal_categorias (perfil_id, chave, nome, cor, tipo, posicao) VALUES (10, 'outros', 'Outros', '#000', 'despesa', 0)");

    $id = ajustarSaldo($pdo, 1, 10, 1, '500');
    assert_igual(null, $id, 'saldo já estava certo — nenhum lançamento criado');

    $id2 = ajustarSaldo($pdo, 1, 10, 1, '500,00');
    assert_igual(null, $id2, 'mesmo valor em formato BR (vírgula) também não cria nada — moeda_float() normaliza');

    $qtdLancamentos = (int) $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_lancamentos")->fetchColumn();
    assert_igual(0, $qtdLancamentos, 'nenhuma linha gravada em nenhum dos dois casos');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Caixinhas descontam do saldo atual, igual já valia no cálculo de index() ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, saldo_inicial) VALUES (1, 1, 10, 'Carteira', 1000)");
    $pdo->exec("INSERT INTO financeiro_pessoal_categorias (perfil_id, chave, nome, cor, tipo, posicao) VALUES
        (10, 'moradia', 'Moradia', '#000', 'despesa', 0), (10, 'salario', 'Salário', '#000', 'receita', 1)");
    // R$ 200,00 guardados numa caixinha a partir desta conta — saldo atual já é 1000 - 200 = 800,
    // não 1000, mesmo sem nenhum lançamento em financeiro_pessoal_lancamentos.
    $pdo->exec("INSERT INTO caixinha_movimentos (conta_id, tipo, valor_centavos) VALUES (1, 'deposito', 20000)");

    $id = ajustarSaldo($pdo, 1, 10, 1, '800');
    assert_igual(null, $id, 'saldo já bate (1000 - 200 de caixinha = 800) — nenhum ajuste necessário');

    $id2 = ajustarSaldo($pdo, 1, 10, 1, '1000');
    assert_verdadeiro($id2 !== null, 'corrigir pra 1000 cria ajuste, já que o saldo real (descontada a caixinha) é 800');
    $lanc = $pdo->query("SELECT * FROM financeiro_pessoal_lancamentos WHERE id = $id2")->fetch();
    assert_igual('receita', $lanc['tipo'], 'diferença de +200 vira receita');
    assert_igual('200', (string) (int) $lanc['valor'], 'valor da diferença considera o desconto da caixinha (1000 - 800)');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Isolamento por perfil — não ajusta conta de outro perfil ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, saldo_inicial) VALUES (1, 1, 10, 'Carteira', 0)");
    $pdo->exec("INSERT INTO financeiro_pessoal_categorias (perfil_id, chave, nome, cor, tipo, posicao) VALUES (10, 'outros', 'Outros', '#000', 'despesa', 0)");

    $id = ajustarSaldo($pdo, 1, 20, 1, '5000'); // perfil 20, mas a conta 1 é do perfil 10
    assert_igual(null, $id, 'perfil errado não acha a conta, nada é criado');

    $qtdLancamentos = (int) $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_lancamentos")->fetchColumn();
    assert_igual(0, $qtdLancamentos, 'nenhum lançamento vazou pro perfil errado');
}

echo "\n------------------------------------------------------------\n";
echo "$total verificações, $falhas falha(s).\n";
exit($falhas > 0 ? 1 : 0);
