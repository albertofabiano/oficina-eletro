<?php
/*
 * Isolamento entre usuários no Fixa — "Toda consulta filtra por usuario_id e perfil_id"
 * (pedido explícito da Fase 1). Testa App\Services\Fixa\PerfilService DE VERDADE (sem
 * reimplementar a lógica) contra um SQLite em memória, mais a mesma forma de consulta
 * (`WHERE usuario_id = ? AND perfil_id = ?`) que FinanceiroPessoalController usa em todo
 * endpoint de lançamento/categoria/conta.
 * Rodar com: php tests/fixa_isolamento_test.php
 */

define('BASE_PATH', dirname(__DIR__));

// App\Core\DB nunca é usado por PerfilService (recebe \PDO por parâmetro) — não precisa de
// stub nenhum, só requer a classe direto.
require BASE_PATH . '/app/Services/Fixa/PerfilService.php';

use App\Services\Fixa\PerfilService;

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

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->sqliteCreateFunction('CURDATE', function () { return date('Y-m-d'); });

$pdo->exec("CREATE TABLE usuarios (id INTEGER PRIMARY KEY, nome TEXT)");
$pdo->exec("CREATE TABLE financeiro_pessoal_perfis (
    id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, tipo TEXT NOT NULL DEFAULT 'pf',
    nome TEXT NOT NULL, documento TEXT, cor TEXT NOT NULL DEFAULT '#8C7CFF', regime TEXT,
    ordem INTEGER NOT NULL DEFAULT 0, arquivado INTEGER NOT NULL DEFAULT 0, criado_em TEXT DEFAULT CURRENT_TIMESTAMP
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
    valor REAL NOT NULL, data_hora TEXT NOT NULL, vencimento TEXT, pago_em TEXT, criado_em TEXT DEFAULT CURRENT_TIMESTAMP
)");

// ── Dois usuários, cada um com seu próprio perfil "Pessoal" (via criarPerfilPessoalPadrao,
// o MESMO método que o controller chama no primeiro acesso de um usuário) ─────────────────
$pdo->exec("INSERT INTO usuarios (id, nome) VALUES (10, 'Usuária A'), (20, 'Usuário B')");
$perfilA = PerfilService::criarPerfilPessoalPadrao($pdo, 10);
$perfilB = PerfilService::criarPerfilPessoalPadrao($pdo, 20);

echo "-- Perfis e contas criados, um por usuário --\n";
assert_verdadeiro((int) $perfilA['id'] !== (int) $perfilB['id'], 'perfis de A e B têm ids diferentes');

$contaA = PerfilService::contasDoPerfil($pdo, (int) $perfilA['id'])[0];
$contaB = PerfilService::contasDoPerfil($pdo, (int) $perfilB['id'])[0];
assert_verdadeiro((int) $contaA['id'] !== (int) $contaB['id'], 'contas de A e B têm ids diferentes');

echo "\n-- perfisDoUsuario() nunca devolve perfil de outro usuário --\n";
{
    $listaA = PerfilService::perfisDoUsuario($pdo, 10);
    $listaB = PerfilService::perfisDoUsuario($pdo, 20);
    assert_igual(1, count($listaA), 'usuário A só vê o próprio perfil (1)');
    assert_igual(1, count($listaB), 'usuário B só vê o próprio perfil (1)');
    assert_igual((int) $perfilA['id'], (int) $listaA[0]['id'], 'perfil devolvido pra A é mesmo o dele');
    assert_igual((int) $perfilB['id'], (int) $listaB[0]['id'], 'perfil devolvido pra B é mesmo o dele');
}

echo "\n-- pertenceAoUsuario() rejeita perfil de OUTRO usuário --\n";
{
    $ok = PerfilService::pertenceAoUsuario($pdo, (int) $perfilA['id'], 10);
    $cruzado = PerfilService::pertenceAoUsuario($pdo, (int) $perfilA['id'], 20); // B tentando usar o perfil de A
    assert_verdadeiro($ok !== null, 'A acessando o PRÓPRIO perfil: permitido');
    assert_verdadeiro($cruzado === null, 'B tentando acessar o perfil de A: rejeitado (null)');
}

echo "\n-- Categorias: mesma CHAVE pode existir em dois perfis diferentes, sem se misturar --\n";
{
    $catsA = PerfilService::categoriasDoPerfil($pdo, (int) $perfilA['id'], 'pf');
    $catsB = PerfilService::categoriasDoPerfil($pdo, (int) $perfilB['id'], 'pf');
    assert_verdadeiro(array_key_exists('outros', $catsA), 'perfil A tem a categoria padrão "outros"');
    assert_verdadeiro(array_key_exists('outros', $catsB), 'perfil B também tem (independente) a categoria "outros"');
    assert_verdadeiro($catsA['outros']['id'] !== $catsB['outros']['id'], '"outros" de A e "outros" de B são LINHAS diferentes no banco (ids distintos)');

    // Renomear a categoria de A não pode afetar a de B, mesmo mesma chave.
    $pdo->prepare("UPDATE financeiro_pessoal_categorias SET nome = 'Só de A' WHERE id = ?")->execute([$catsA['outros']['id']]);
    $catsBDepois = PerfilService::categoriasDoPerfil($pdo, (int) $perfilB['id'], 'pf');
    assert_igual('Outros', $catsBDepois['outros']['nome'], 'editar a categoria de A não muda a categoria (mesma chave) de B');
}

echo "\n-- Lançamentos: query com usuario_id + perfil_id (padrão do controller) isola de verdade --\n";
{
    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (usuario_id, perfil_id, conta_id, tipo, categoria, descricao, valor, data_hora)
        VALUES (10, {$perfilA['id']}, {$contaA['id']}, 'despesa', 'outros', 'Gasto de A', 100, '2026-10-01 10:00:00')");
    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (usuario_id, perfil_id, conta_id, tipo, categoria, descricao, valor, data_hora)
        VALUES (20, {$perfilB['id']}, {$contaB['id']}, 'despesa', 'outros', 'Gasto de B', 999, '2026-10-01 10:00:00')");

    // Mesma consulta usada em buscarLancamentosDoMes() — dupla camada usuario_id + perfil_id.
    $buscar = function (int $usuarioId, int $perfilId) use ($pdo) {
        $st = $pdo->prepare("SELECT * FROM financeiro_pessoal_lancamentos WHERE usuario_id = ? AND perfil_id = ?");
        $st->execute([$usuarioId, $perfilId]);
        return $st->fetchAll();
    };

    $resultA = $buscar(10, (int) $perfilA['id']);
    assert_igual(1, count($resultA), 'A busca com seu usuario_id+perfil_id só acha o PRÓPRIO lançamento');
    assert_igual('Gasto de A', $resultA[0]['descricao'], 'é de fato o lançamento de A');

    // Usuário 10 (A) tentando usar o perfil_id de B (POST forjado/adulterado) — a dupla
    // camada (usuario_id bate, mas perfil_id não é de nenhum perfil DO USUÁRIO 10) não acha
    // nada, mesmo o perfil_id sendo válido pra OUTRO usuário.
    $forjado = $buscar(10, (int) $perfilB['id']);
    assert_igual(0, count($forjado), 'A forjando o perfil_id de B: zero resultados (nunca vê o lançamento de B)');

    // E o caminho inverso: perfil_id certo, usuario_id errado — também não vaza.
    $forjado2 = $buscar(20, (int) $perfilA['id']);
    assert_igual(0, count($forjado2), 'usuario_id de B + perfil_id de A: zero resultados (as duas camadas precisam bater)');
}

echo "\n-- arquivarPerfil() nunca deixa o usuário sem nenhum perfil ativo --\n";
{
    $resultado = PerfilService::arquivarPerfil($pdo, (int) $perfilA['id'], 10, true);
    assert_verdadeiro($resultado === false, 'arquivar o ÚNICO perfil ativo de A é recusado (false)');
    $aindaAtivo = PerfilService::perfisDoUsuario($pdo, 10);
    assert_igual(1, count($aindaAtivo), 'perfil de A continua ativo depois da tentativa recusada');

    // B tentando arquivar o perfil de A — nem chega a checar "é o único", falha antes por não
    // pertencer a ele.
    $cruzado = PerfilService::arquivarPerfil($pdo, (int) $perfilA['id'], 20, true);
    assert_verdadeiro($cruzado === false, 'B tentando arquivar o perfil de A: recusado (não é dono)');
}

echo "\n" . str_repeat('-', 60) . "\n";
echo $total . " verificações, " . $falhas . " falha(s).\n";
exit($falhas > 0 ? 1 : 0);
