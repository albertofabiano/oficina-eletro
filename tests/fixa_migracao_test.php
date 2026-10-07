<?php
/*
 * Testa scripts/migrar_fixa_perfis.php DE VERDADE (não uma réplica da lógica) contra um
 * SQLite em memória (arquivo temporário) — injeta um stub de App\Core\DB::pdo() via
 * `auto_prepend_file` (carrega ANTES do autoloader do próprio script, então quando o script
 * referencia App\Core\DB a classe já existe e o autoload real nunca chega a rodar) — mesma
 * técnica de teste de script real já usada nesta sessão (ver histórico da feature de
 * vencimento/pago_em do Financeiro Pessoal).
 *
 * Cobre a regra central pedida: "compare a soma dos lançamentos por usuário antes e depois ...
 * tem que ser igual" — e confere isolamento (só o usuário passado em --usuario é tocado).
 *
 * Rodar com: php tests/fixa_migracao_test.php
 */

define('BASE_PATH', dirname(__DIR__));

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

$dbPath = sys_get_temp_dir() . '/fixa_migracao_test_' . bin2hex(random_bytes(4)) . '.sqlite';
$stubPath = sys_get_temp_dir() . '/fixa_migracao_stub_' . bin2hex(random_bytes(4)) . '.php';

function montarSchema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE usuarios (id INTEGER PRIMARY KEY, nome TEXT)");
    $pdo->exec("CREATE TABLE financeiro_pessoal_perfis (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, tipo TEXT NOT NULL DEFAULT 'pf',
        nome TEXT NOT NULL, documento TEXT, cor TEXT NOT NULL DEFAULT '#8C7CFF', regime TEXT,
        ordem INTEGER NOT NULL DEFAULT 0, arquivado INTEGER NOT NULL DEFAULT 0,
        criado_em TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE financeiro_pessoal_contas (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER NOT NULL,
        nome TEXT NOT NULL, tipo TEXT NOT NULL DEFAULT 'dinheiro', saldo_inicial REAL NOT NULL DEFAULT 0,
        data_saldo_inicial TEXT NOT NULL, cor TEXT NOT NULL DEFAULT '#3CC9C0', arquivada INTEGER NOT NULL DEFAULT 0,
        criado_em TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE financeiro_pessoal_categorias (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER,
        chave TEXT NOT NULL, nome TEXT NOT NULL, tipo TEXT NOT NULL DEFAULT 'despesa', cor TEXT NOT NULL DEFAULT '#7A6A88',
        icone TEXT, grupo_dre TEXT, ativo INTEGER NOT NULL DEFAULT 1, posicao INTEGER NOT NULL DEFAULT 0,
        criado_em TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE financeiro_pessoal_lancamentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER, conta_id INTEGER,
        tipo TEXT NOT NULL DEFAULT 'despesa', categoria TEXT NOT NULL DEFAULT 'outros', descricao TEXT NOT NULL,
        valor REAL NOT NULL, data_hora TEXT NOT NULL, vencimento TEXT, pago_em TEXT,
        data_competencia TEXT, observacao TEXT, anexo_url TEXT, codigo_barras TEXT, pix_copia_cola TEXT,
        hora_informada INTEGER NOT NULL DEFAULT 1, origem TEXT NOT NULL DEFAULT 'manual', item_id INTEGER,
        criado_em TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE financeiro_pessoal_eventos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER, lancamento_id INTEGER,
        titulo TEXT NOT NULL, data_hora TEXT NOT NULL, lido_em TEXT, criado_em TEXT DEFAULT CURRENT_TIMESTAMP
    )");
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    montarSchema($pdo);

    // ── Dados de partida: usuário 42, "Sergio", com 2 lançamentos (passado + futuro), uma
    // categoria órfã ("ffffff", usada num lançamento mas sem linha em _categorias — mesmo
    // padrão real já achado em produção nesta sessão), e um usuário 2 (isolamento). ──────────
    $pdo->exec("INSERT INTO usuarios (id, nome) VALUES (42, 'Sergio Martins'), (2, 'Outro Usuário')");

    $hoje = date('Y-m-d H:i:s');
    $ontem = date('Y-m-d H:i:s', strtotime('-1 day'));
    $amanha = date('Y-m-d H:i:s', strtotime('+2 day'));

    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (usuario_id, tipo, categoria, descricao, valor, data_hora)
        VALUES (42, 'despesa', 'ffffff', 'Compra teste', 150.50, '{$ontem}')");
    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (usuario_id, tipo, categoria, descricao, valor, data_hora)
        VALUES (42, 'receita', 'outros', 'Entrada futura', 500.00, '{$amanha}')");
    // "Recebido de empréstimo" — a migração deve só LISTAR, nunca alterar.
    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (usuario_id, tipo, categoria, descricao, valor, data_hora)
        VALUES (42, 'receita', 'outros', 'Recebido de empréstimo do Carlos', 1000.00, '{$ontem}')");
    $pdo->exec("INSERT INTO financeiro_pessoal_eventos (usuario_id, titulo, data_hora) VALUES (42, 'Consulta', '{$hoje}')");

    // Usuário 2 — nunca deve ser tocado quando o script roda com --usuario=42.
    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (usuario_id, tipo, categoria, descricao, valor, data_hora)
        VALUES (2, 'despesa', 'outros', 'Lançamento do outro usuário', 77.00, '{$ontem}')");

    $somaAntes = (float) $pdo->query("SELECT COALESCE(SUM(valor),0) FROM financeiro_pessoal_lancamentos WHERE usuario_id = 42")->fetchColumn();
    $qtdAntes = (int) $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_lancamentos WHERE usuario_id = 42")->fetchColumn();

    // ── Stub de App\Core\DB::pdo() apontando pro mesmo arquivo SQLite, via auto_prepend_file —
    // carrega ANTES do spl_autoload_register do próprio script, então a classe já existe
    // quando ele referencia App\Core\DB::pdo() (autoload nunca chega a ser chamado pra ela). ──
    file_put_contents($stubPath, '<?php
namespace App\Core {
    class DB {
        public static function pdo(): \PDO {
            static $pdo = null;
            if ($pdo === null) {
                $pdo = new \PDO("sqlite:' . $dbPath . '");
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
                $pdo->sqliteCreateFunction("CURDATE", function () { return date("Y-m-d"); });
                $pdo->sqliteCreateFunction("NOW", function () { return date("Y-m-d H:i:s"); });
            }
            return $pdo;
        }
    }
}
');

    $cmd = 'php -d auto_prepend_file=' . escapeshellarg($stubPath) . ' '
         . escapeshellarg(BASE_PATH . '/scripts/migrar_fixa_perfis.php')
         . ' --aplicar --usuario=42 2>&1';
    $saida = shell_exec($cmd);

    echo "-- Saída do script (scripts/migrar_fixa_perfis.php --aplicar --usuario=42) --\n";
    echo $saida . "\n";

    // ── Verificações ─────────────────────────────────────────────────────────────────────
    assert_verdadeiro(strpos((string) $saida, 'DIVERGIU') === false, 'script não relatou divergência de soma');
    assert_verdadeiro(strpos((string) $saida, 'Recebido de empréstimo') !== false, 'script listou o lançamento "recebido de empréstimo" pra revisão');

    $somaDepois = (float) $pdo->query("SELECT COALESCE(SUM(valor),0) FROM financeiro_pessoal_lancamentos WHERE usuario_id = 42")->fetchColumn();
    $qtdDepois = (int) $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_lancamentos WHERE usuario_id = 42")->fetchColumn();
    assert_igual($somaAntes, $somaDepois, 'soma de lançamentos do usuário 42 é IGUAL antes e depois da migração');
    assert_igual($qtdAntes, $qtdDepois, 'quantidade de lançamentos do usuário 42 não muda (nada é criado/apagado)');

    $perfil = $pdo->query("SELECT * FROM financeiro_pessoal_perfis WHERE usuario_id = 42")->fetch(PDO::FETCH_ASSOC);
    assert_verdadeiro($perfil !== false, 'perfil "Pessoal" foi criado pro usuário 42');
    assert_igual('Pessoal', $perfil['nome'] ?? null, 'perfil criado chama "Pessoal"');
    assert_igual('pf', $perfil['tipo'] ?? null, 'perfil criado é do tipo pf');

    $conta = $pdo->query("SELECT * FROM financeiro_pessoal_contas WHERE perfil_id = " . (int) $perfil['id'])->fetch(PDO::FETCH_ASSOC);
    assert_verdadeiro($conta !== false, 'conta "Carteira" foi criada no perfil');
    assert_igual('Carteira', $conta['nome'] ?? null, 'conta criada chama "Carteira"');

    $lancPassado = $pdo->query("SELECT * FROM financeiro_pessoal_lancamentos WHERE usuario_id=42 AND descricao='Compra teste'")->fetch(PDO::FETCH_ASSOC);
    assert_igual((int) $perfil['id'], (int) $lancPassado['perfil_id'], 'lançamento do passado ganhou o perfil_id certo');
    assert_igual((int) $conta['id'], (int) $lancPassado['conta_id'], 'lançamento do passado ganhou o conta_id certo');
    assert_igual(substr($ontem, 0, 10), $lancPassado['pago_em'], 'lançamento com data NO PASSADO ganhou pago_em = a própria data');

    $lancFuturo = $pdo->query("SELECT * FROM financeiro_pessoal_lancamentos WHERE usuario_id=42 AND descricao='Entrada futura'")->fetch(PDO::FETCH_ASSOC);
    assert_igual(null, $lancFuturo['pago_em'], 'lançamento com data NO FUTURO continua em aberto (pago_em nulo)');

    $catOrfa = $pdo->query("SELECT * FROM financeiro_pessoal_categorias WHERE perfil_id = " . (int) $perfil['id'] . " AND chave = 'ffffff'")->fetch(PDO::FETCH_ASSOC);
    assert_verdadeiro($catOrfa !== false, 'categoria órfã "ffffff" (usada num lançamento, sem linha própria) foi resgatada como categoria real');

    $evento = $pdo->query("SELECT * FROM financeiro_pessoal_eventos WHERE usuario_id = 42")->fetch(PDO::FETCH_ASSOC);
    assert_igual((int) $perfil['id'], (int) $evento['perfil_id'], 'evento da Agenda ganhou o perfil_id certo');

    // ── Isolamento: usuário 2 nunca foi tocado (rodamos só --usuario=42) ───────────────────
    $perfilOutro = $pdo->query("SELECT * FROM financeiro_pessoal_perfis WHERE usuario_id = 2")->fetch(PDO::FETCH_ASSOC);
    assert_verdadeiro($perfilOutro === false, 'usuário 2 (fora do --usuario=42) não ganhou perfil nenhum — isolamento respeitado');
    $lancOutro = $pdo->query("SELECT perfil_id FROM financeiro_pessoal_lancamentos WHERE usuario_id = 2")->fetch(PDO::FETCH_ASSOC);
    assert_igual(null, $lancOutro['perfil_id'], 'lançamento do usuário 2 continua sem perfil_id — não foi tocado');

    // ── Rodar de novo (idempotência) não deve duplicar perfil nem recriar a categoria já resgatada ──
    shell_exec($cmd);
    $qtdPerfis = (int) $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_perfis WHERE usuario_id = 42")->fetchColumn();
    assert_igual(1, $qtdPerfis, 'rodar o script de novo não duplica o perfil "Pessoal"');
    $qtdCatOrfa = (int) $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_categorias WHERE perfil_id = " . (int) $perfil['id'] . " AND chave = 'ffffff'")->fetchColumn();
    assert_igual(1, $qtdCatOrfa, 'rodar o script de novo não duplica a categoria resgatada "ffffff"');
} finally {
    @unlink($dbPath);
    @unlink($stubPath);
}

echo "\n" . str_repeat('-', 60) . "\n";
echo $total . " verificações, " . $falhas . " falha(s).\n";
exit($falhas > 0 ? 1 : 0);
