<?php
/*
 * Aviso de vencimento de licença do plano completo (scripts/avisar_vencimento_licenca.php) —
 * testa a SELEÇÃO de empresas elegíveis (réplica da query real, mesma fórmula de "vencimento
 * efetivo" = GREATEST(trial_ate, licenca_ate), registrada como função custom no SQLite — o
 * script real usa GREATEST() nativo do MySQL, que o SQLite não tem) e o DEDUP via
 * empresa_avisos_vencimento (UNIQUE empresa_id+tipo+data_vencimento) contra SQLite em memória.
 *
 * Não testa EmailService::avisoVencimentoLicenca()/NotificacaoService::criar() de verdade (sem
 * config/email.php real neste ambiente) — só confirma que o método não lança exceção sem
 * config (mesmo padrão de segurança já usado nos outros testes de e-mail deste projeto).
 *
 * Rodar com: php tests/aviso_vencimento_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Helpers/functions.php';
require BASE_PATH . '/app/Services/EmailService.php';

use App\Services\EmailService;

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
    // GREATEST(trial_ate, licenca_ate) ignorando NULL — mesma fórmula do script real, só que
    // o MySQL tem GREATEST() nativo e o SQLite não; registrado aqui pra rodar a MESMA query.
    $pdo->sqliteCreateFunction('GREATEST', function (...$valores) {
        $valores = array_filter($valores, fn($v) => $v !== null);
        return $valores ? max($valores) : null;
    });

    $pdo->exec("CREATE TABLE empresas (
        id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT, plano_atual TEXT,
        trial_ate TEXT, licenca_ate TEXT, ativo INTEGER DEFAULT 1, reivindicada INTEGER DEFAULT 1,
        tipo_conta TEXT DEFAULT 'completo', razao_social TEXT, nome_fantasia TEXT
    )");
    $pdo->exec("CREATE TABLE usuarios (id INTEGER PRIMARY KEY, empresa_id INTEGER, nome TEXT, perfil TEXT, telefone TEXT)");
    $pdo->exec("CREATE TABLE empresa_avisos_vencimento (
        id INTEGER PRIMARY KEY AUTOINCREMENT, empresa_id INTEGER NOT NULL, tipo TEXT NOT NULL,
        data_vencimento TEXT NOT NULL, enviado_em TEXT DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(empresa_id, tipo, data_vencimento)
    )");
    return $pdo;
}

/**
 * Mesma seleção de buscarEmpresasNoVencimento() em scripts/avisar_vencimento_licenca.php, só
 * que como subquery + WHERE em vez de HAVING sem GROUP BY — o MySQL real aceita HAVING
 * referenciando um alias do SELECT mesmo sem agregação (é o que o script de produção usa); o
 * SQLite é mais estrito e recusa esse padrão, então o teste usa uma forma equivalente.
 */
function buscarEmpresasNoVencimento(PDO $db, string $dataAlvo): array
{
    $stmt = $db->prepare(
        "SELECT * FROM (
            SELECT e.id, e.email, e.plano_atual,
                   GREATEST(COALESCE(e.trial_ate, '1970-01-01'), COALESCE(e.licenca_ate, '1970-01-01')) AS vencimento_efetivo
            FROM empresas e
            WHERE e.ativo = 1 AND e.reivindicada = 1 AND e.tipo_conta = 'completo'
              AND e.email IS NOT NULL AND e.email <> ''
         ) t
         WHERE t.vencimento_efetivo = ?"
    );
    $stmt->execute([$dataAlvo]);
    return $stmt->fetchAll();
}

/** Mesmo check-then-insert do script real — devolve true se enviou (não estava duplicado). */
function processarAviso(PDO $db, int $empresaId, string $tipo, string $dataVenc): bool
{
    $jaEnviado = $db->prepare("SELECT 1 FROM empresa_avisos_vencimento WHERE empresa_id = ? AND tipo = ? AND data_vencimento = ?");
    $jaEnviado->execute([$empresaId, $tipo, $dataVenc]);
    if ($jaEnviado->fetchColumn()) return false;
    $db->prepare("INSERT INTO empresa_avisos_vencimento (empresa_id, tipo, data_vencimento) VALUES (?, ?, ?)")
        ->execute([$empresaId, $tipo, $dataVenc]);
    return true;
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "== Seleção de empresas no vencimento (vencimento_efetivo = GREATEST ignorando NULL) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $hoje = date('Y-m-d');
    $em3Dias = date('Y-m-d', strtotime('+3 days'));

    $pdo->exec("INSERT INTO empresas (id, email, plano_atual, trial_ate, licenca_ate, tipo_conta, reivindicada)
                VALUES (1, 'a@x.com', 'autonomo', NULL, '{$hoje}', 'completo', 1)");           // vence hoje, só licenca_ate
    $pdo->exec("INSERT INTO empresas (id, email, plano_atual, trial_ate, licenca_ate, tipo_conta, reivindicada)
                VALUES (2, 'b@x.com', NULL, '{$em3Dias}', NULL, 'completo', 1)");               // vence em 3 dias, só trial
    $pdo->exec("INSERT INTO empresas (id, email, plano_atual, trial_ate, licenca_ate, tipo_conta, reivindicada)
                VALUES (3, 'c@x.com', 'oficina', '2020-01-01', '{$hoje}', 'completo', 1)");      // licenca_ate manda (mais recente que trial velho)
    $pdo->exec("INSERT INTO empresas (id, email, plano_atual, trial_ate, licenca_ate, tipo_conta, reivindicada)
                VALUES (4, 'd@x.com', 'autonomo', NULL, '{$hoje}', 'diretorio', 1)");            // tipo_conta errado, não entra
    $pdo->exec("INSERT INTO empresas (id, email, plano_atual, trial_ate, licenca_ate, tipo_conta, reivindicada)
                VALUES (5, 'e@x.com', 'autonomo', NULL, '{$hoje}', 'completo', 0)");             // não reivindicada, não entra
    $pdo->exec("INSERT INTO empresas (id, email, plano_atual, trial_ate, licenca_ate, tipo_conta, reivindicada)
                VALUES (6, '', 'autonomo', NULL, '{$hoje}', 'completo', 1)");                    // sem e-mail, não entra

    $hojeRes = buscarEmpresasNoVencimento($pdo, $hoje);
    $ids = array_column($hojeRes, 'id');
    sort($ids);
    assert_igual([1, 3], $ids, 'vencimento hoje: só as empresas 1 e 3 (completo, reivindicada, com e-mail)');

    $em3Res = buscarEmpresasNoVencimento($pdo, $em3Dias);
    assert_igual([2], array_column($em3Res, 'id'), 'vencimento em 3 dias: só a empresa 2');

    assert_igual(0, count(buscarEmpresasNoVencimento($pdo, '2099-01-01')), 'data sem nenhuma empresa correspondente devolve vazio');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Dedup — nunca manda o mesmo aviso duas vezes pra MESMA data de vencimento ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();

    assert_verdadeiro(processarAviso($pdo, 10, 'vencimento', '2026-10-10'), '1º envio pra essa empresa/tipo/data: processa');
    assert_verdadeiro(!processarAviso($pdo, 10, 'vencimento', '2026-10-10'), '2º envio IDÊNTICO (mesmo dia, cron rodando 2x): pulado');
    assert_verdadeiro(!processarAviso($pdo, 10, 'vencimento', '2026-10-10'), '3º envio idêntico também continua pulado');

    $totalLinhas = (int) $pdo->query("SELECT COUNT(*) FROM empresa_avisos_vencimento WHERE empresa_id = 10")->fetchColumn();
    assert_igual(1, $totalLinhas, 'só 1 linha gravada, apesar de 3 tentativas');

    assert_verdadeiro(processarAviso($pdo, 10, '3_dias_antes', '2026-10-10'), 'tipo diferente (3_dias_antes vs vencimento), mesma data: processa independente');

    // Renovou — nova data de vencimento, mesma empresa/tipo: elegível de novo, sem precisar
    // resetar nada manualmente (é exatamente por isso que a UNIQUE inclui a data).
    assert_verdadeiro(processarAviso($pdo, 10, 'vencimento', '2026-11-10'), 'renovou (nova data_vencimento): elegível de novo pro aviso "vencimento"');

    $totalLinhasFinal = (int) $pdo->query("SELECT COUNT(*) FROM empresa_avisos_vencimento WHERE empresa_id = 10")->fetchColumn();
    assert_igual(3, $totalLinhasFinal, '3 linhas no total: vencimento/data1, 3_dias_antes/data1, vencimento/data2');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== EmailService::avisoVencimentoLicenca() — nunca lança exceção sem config/email.php ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $ok = EmailService::avisoVencimentoLicenca('teste@example.com', 'Maria', true, '10/10/2026', 'https://checkout.exemplo/abc');
    assert_igual(false, $ok, 'sem config/email.php real neste ambiente, cai no fallback seguro (false), sem erro fatal');
}

echo "\n------------------------------------------------------------\n";
echo "$total verificações, $falhas falha(s).\n";
exit($falhas > 0 ? 1 : 0);
