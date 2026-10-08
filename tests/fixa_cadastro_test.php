<?php
/*
 * Cadastro próprio do Carteira Fixa standalone (FixaCadastroController) — testa as regras de
 * validação DE VERDADE contra config/planos_fixa.php (nunca inventa plano/ciclo válido na mão),
 * a criação da empresa "casca" + usuário + teste (via AssinaturaService::criarTeste(), real)
 * contra SQLite em memória, e a rota de acesso restrito de Auth::soFixa() (replicada — mesma
 * lógica exata de app/Middleware/AuthMiddleware.php, que não dá pra instanciar fora de um
 * request HTTP de verdade).
 *
 * Rodar com: php tests/fixa_cadastro_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Helpers/functions.php';
require BASE_PATH . '/app/Services/Fixa/AssinaturaService.php';

use App\Services\Fixa\AssinaturaService;

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

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "== Validação de plano/ciclo (mesma whitelist de FixaCadastroController::cadastrarSalvar()) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $cfg = AssinaturaService::config();
    $planosValidos = array_column($cfg['planos'], null, 'codigo');
    $ciclosValidos = $cfg['ciclos'];

    assert_verdadeiro(isset($planosValidos['fixa_individual']), '"fixa_individual" é um plano válido');
    assert_verdadeiro(isset($planosValidos['fixa_diretorio']), '"fixa_diretorio" é um plano válido');
    assert_igual(false, isset($planosValidos['plano_inventado']), 'plano forjado por POST direto nunca é aceito');
    assert_igual(false, isset($planosValidos['']), 'plano vazio nunca é aceito');

    assert_verdadeiro(isset($ciclosValidos['mensal']) && isset($ciclosValidos['trimestral'])
        && isset($ciclosValidos['semestral']) && isset($ciclosValidos['anual']), 'os 4 ciclos existem na config');
    assert_igual(false, isset($ciclosValidos['bimestral']), 'ciclo forjado nunca é aceito (cai pro default "mensal" no controller)');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Cadastro de verdade: empresa \"casca\" + usuário + teste (contra SQLite) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec("CREATE TABLE empresas (
        id INTEGER PRIMARY KEY AUTOINCREMENT, razao_social TEXT, email TEXT,
        tipo_conta TEXT NOT NULL DEFAULT 'completo', reivindicada INTEGER DEFAULT 0,
        listagem_publica INTEGER DEFAULT 1, ativo INTEGER DEFAULT 1
    )");
    $pdo->exec("CREATE TABLE usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT, empresa_id INTEGER NOT NULL,
        nome TEXT, email TEXT, senha TEXT, perfil TEXT DEFAULT 'tecnico', ativo INTEGER DEFAULT 1
    )");
    $pdo->exec("CREATE TABLE fixa_assinaturas (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER, plano TEXT, ciclo TEXT,
        status TEXT, teste_inicio TEXT, teste_fim TEXT, data_inicio TEXT, data_fim TEXT,
        valor_centavos INTEGER, credito_centavos INTEGER DEFAULT 0,
        indicado_por_usuario_id INTEGER, tentativas_falhas INTEGER DEFAULT 0,
        ultima_tentativa_em TEXT, bloqueada_em TEXT, cancelada_em TEXT, cancelar_token TEXT
    )");

    // Mesma sequência exata (menos e-mail/best-effort) de FixaCadastroController::cadastrarSalvar().
    $nome = 'Maria Teste'; $email = 'maria@example.com';
    $pdo->prepare(
        "INSERT INTO empresas (razao_social, email, tipo_conta, reivindicada, listagem_publica, ativo)
         VALUES (?, ?, 'fixa', 0, 0, 1)"
    )->execute([$nome, $email]);
    $empresaId = (int) $pdo->lastInsertId();

    $pdo->prepare("INSERT INTO usuarios (empresa_id, nome, email, senha, perfil, ativo) VALUES (?, ?, ?, 'hash', 'admin', 1)")
        ->execute([$empresaId, $nome, $email]);
    $usuarioId = (int) $pdo->lastInsertId();

    AssinaturaService::criarTeste($pdo, $usuarioId, 'fixa_individual', 'mensal');

    $emp = $pdo->query("SELECT * FROM empresas WHERE id = $empresaId")->fetch();
    assert_igual('fixa', $emp['tipo_conta'], 'empresa casca nasce com tipo_conta="fixa"');
    assert_igual(0, (int) $emp['reivindicada'], 'nunca "reivindicada" (não é assistência técnica de verdade)');
    assert_igual(0, (int) $emp['listagem_publica'], 'listagem_publica=0 — nunca aparece no Diretório');

    $assinatura = AssinaturaService::doUsuario($pdo, $usuarioId);
    assert_igual('teste', $assinatura['status'], 'assinatura nasce em teste');
    assert_igual('fixa_individual', $assinatura['plano'], 'plano gravado bate com o escolhido no cadastro');
    assert_verdadeiro(AssinaturaService::acessoCompleto($assinatura), 'usuário recém-cadastrado já tem acesso completo (dentro do teste)');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Rota liberada pra conta Carteira Fixa standalone (Auth::soFixa(), replicado) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
// Mesma lista/condição exata do bloco novo em AuthMiddleware::handle() — não dá pra instanciar
// o middleware fora de um request HTTP real (lê $_SERVER/header()/exit), então a condição de
// roteamento é replicada aqui, igual já é feito pros outros guards deste módulo.
{
    $liberadoParaFixa = function (string $uri): bool {
        $liberado = ['/financeiro-pessoal', '/carteira-fixa', '/fixa', '/logout'];
        foreach ($liberado as $p) { if ($uri === $p || str_starts_with($uri, $p . '/')) { return true; } }
        return false;
    };

    assert_igual(true, $liberadoParaFixa('/financeiro-pessoal'), '/financeiro-pessoal liberado');
    assert_igual(true, $liberadoParaFixa('/financeiro-pessoal/lancamentos'), '/financeiro-pessoal/lancamentos liberado');
    assert_igual(true, $liberadoParaFixa('/carteira-fixa/forma-pagamento'), '/carteira-fixa/forma-pagamento liberado');
    assert_igual(true, $liberadoParaFixa('/fixa/cancelar-teste/abc123'), '/fixa/cancelar-teste/{token} liberado');
    assert_igual(true, $liberadoParaFixa('/carteira-fixa/login'), '/carteira-fixa/login liberado (porta de entrada própria)');
    assert_igual(true, $liberadoParaFixa('/logout'), '/logout sempre liberado');

    assert_igual(false, $liberadoParaFixa('/os'), '/os BLOQUEADO (não é cliente de assistência técnica)');
    assert_igual(false, $liberadoParaFixa('/dashboard'), '/dashboard BLOQUEADO (shell empresa não tem painel de OS)');
    assert_igual(false, $liberadoParaFixa('/assistencias'), '/assistencias BLOQUEADO (nunca aparece no Diretório)');
    assert_igual(false, $liberadoParaFixa('/empresa/perfil-publico'), '/empresa/perfil-publico BLOQUEADO (não tem perfil de Diretório)');
}

echo "\n------------------------------------------------------------\n";
echo "$total verificações, $falhas falha(s).\n";
exit($falhas > 0 ? 1 : 0);
