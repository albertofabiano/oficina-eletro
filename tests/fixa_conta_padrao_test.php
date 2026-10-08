<?php
/*
 * Conta "padrão" do Fixa (Carteira Fixa) — a conta criada AUTOMATICAMENTE junto com o perfil
 * ("Carteira" pra pf, "Conta da empresa" pra pj) nunca pode ser arquivada; contas criadas
 * depois pelo próprio usuário continuam arquiváveis normalmente (pedido explícito do usuário).
 *
 * Testa PerfilService::criarPerfilPessoalPadrao()/criarPerfil() DE VERDADE (sem reimplementar)
 * contra SQLite em memória — confirma que a conta nasce com padrao=1. O guard de
 * FixaContasController::arquivar() é replicado aqui (mesma condição exata: "SELECT padrao ...
 * se 1, recusa"), porque o controller monta `$this->perfil` via App\Core\DB::pdo() direto no
 * construtor (sem injeção de PDO de teste possível) — mesma limitação já documentada noutros
 * testes deste projeto (ver tests/fixa_cobranca_test.php, fixa_scanner_verificar_replica()).
 *
 * Rodar com: php tests/fixa_conta_padrao_test.php
 */

define('BASE_PATH', dirname(__DIR__));
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
    padrao INTEGER NOT NULL DEFAULT 0, criado_em TEXT DEFAULT CURRENT_TIMESTAMP
)");

echo "== criarPerfilPessoalPadrao() — conta \"Carteira\" nasce padrão ==\n";
{
    PerfilService::criarPerfilPessoalPadrao($pdo, 1);
    $conta = $pdo->query("SELECT * FROM financeiro_pessoal_contas WHERE usuario_id = 1")->fetch();
    assert_igual('Carteira', $conta['nome'], 'nome da conta automática é "Carteira"');
    assert_igual(1, (int) $conta['padrao'], 'nasce com padrao=1');
    assert_igual(0, (int) $conta['arquivada'], 'nasce não arquivada (óbvio, mas confirma o default)');
}

echo "\n== criarPerfil() manual (+ Novo perfil) — conta inicial também nasce padrão ==\n";
{
    PerfilService::criarPerfil($pdo, 2, 'pf', 'Outro perfil PF', '', '#111111', null);
    $contaPf = $pdo->query("SELECT * FROM financeiro_pessoal_contas WHERE usuario_id = 2")->fetch();
    assert_igual('Carteira', $contaPf['nome'], 'perfil pf manual também ganha conta "Carteira"');
    assert_igual(1, (int) $contaPf['padrao'], 'conta do perfil pf manual nasce padrao=1');

    PerfilService::criarPerfil($pdo, 3, 'pj', 'Empresa Teste', '12345678000190', '#222222', 'mei');
    $contaPj = $pdo->query("SELECT * FROM financeiro_pessoal_contas WHERE usuario_id = 3")->fetch();
    assert_igual('Conta da empresa', $contaPj['nome'], 'perfil pj ganha conta "Conta da empresa"');
    assert_igual(1, (int) $contaPj['padrao'], 'conta do perfil pj também nasce padrao=1');
}

echo "\n== Conta criada manualmente pelo usuário (\"+ Nova conta\") nasce SEM proteção ==\n";
{
    // Mesmo INSERT que FixaContasController::salvar() faz — sem passar `padrao` nenhum, cai no
    // DEFAULT 0 da coluna (nunca marcado como padrão, só os dois caminhos automáticos acima
    // passam padrao=1 explicitamente).
    $pdo->prepare(
        "INSERT INTO financeiro_pessoal_contas (usuario_id, perfil_id, nome, tipo, saldo_inicial, data_saldo_inicial, cor, arquivada)
         VALUES (1, 1, 'Banco Novo', 'corrente', 0, CURDATE(), '#000000', 0)"
    )->execute();
    $contaManual = $pdo->query("SELECT * FROM financeiro_pessoal_contas WHERE nome = 'Banco Novo'")->fetch();
    assert_igual(0, (int) $contaManual['padrao'], 'conta criada manualmente nasce padrao=0 (arquivável)');
}

echo "\n== Guard de FixaContasController::arquivar() (replicado — ver nota no topo) ==\n";
{
    // Mesma condição exata do controller: SELECT padrao antes do UPDATE; padrao=1 recusa.
    $tentarArquivar = function (PDO $db, int $contaId, int $perfilId): array {
        $st = $db->prepare("SELECT padrao FROM financeiro_pessoal_contas WHERE id = ? AND perfil_id = ?");
        $st->execute([$contaId, $perfilId]);
        if ((int) $st->fetchColumn() === 1) {
            return ['ok' => false, 'erro' => 'Essa é a conta padrão do perfil — ela não pode ser arquivada.'];
        }
        $db->prepare("UPDATE financeiro_pessoal_contas SET arquivada = 1 WHERE id = ? AND perfil_id = ?")
            ->execute([$contaId, $perfilId]);
        return ['ok' => true];
    };

    $carteira = $pdo->query("SELECT id FROM financeiro_pessoal_contas WHERE nome = 'Carteira' AND usuario_id = 1")->fetch();
    $r1 = $tentarArquivar($pdo, (int) $carteira['id'], 1);
    assert_igual(false, $r1['ok'], 'arquivar a conta padrão ("Carteira") é recusado');
    $carteiraDepois = $pdo->query("SELECT arquivada FROM financeiro_pessoal_contas WHERE id = " . (int) $carteira['id'])->fetch();
    assert_igual(0, (int) $carteiraDepois['arquivada'], 'continua não-arquivada depois da tentativa recusada');

    $bancoNovo = $pdo->query("SELECT id FROM financeiro_pessoal_contas WHERE nome = 'Banco Novo'")->fetch();
    $r2 = $tentarArquivar($pdo, (int) $bancoNovo['id'], 1);
    assert_igual(true, $r2['ok'], 'arquivar uma conta NÃO padrão é aceito normalmente');
    $bancoNovoDepois = $pdo->query("SELECT arquivada FROM financeiro_pessoal_contas WHERE id = " . (int) $bancoNovo['id'])->fetch();
    assert_igual(1, (int) $bancoNovoDepois['arquivada'], 'de fato ficou arquivada');

    // Tentar arquivar uma conta de OUTRO perfil (isolamento) — mesma camada dupla usuario/perfil
    // já usada em todo o resto do módulo.
    $contaOutroPerfil = $pdo->query("SELECT id FROM financeiro_pessoal_contas WHERE usuario_id = 2")->fetch();
    $r3 = $tentarArquivar($pdo, (int) $contaOutroPerfil['id'], 1); // perfil_id=1 errado de propósito
    assert_igual(true, $r3['ok'], 'perfil_id errado: UPDATE não encontra a linha, mas não quebra (0 linhas afetadas)');
    $contaOutroPerfilDepois = $pdo->query("SELECT arquivada FROM financeiro_pessoal_contas WHERE id = " . (int) $contaOutroPerfil['id'])->fetch();
    assert_igual(0, (int) $contaOutroPerfilDepois['arquivada'], 'conta de outro perfil não foi arquivada por engano (isolamento)');
}

echo "\n------------------------------------------------------------\n";
echo "$total verificações, $falhas falha(s).\n";
exit($falhas > 0 ? 1 : 0);
