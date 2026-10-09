<?php
/*
 * Aprendizado de categoria (beneficiário/descrição -> categoria/conta) — estendido pra valer em
 * manual e scanner (ver migration 099, financeiro_pessoal_categoria_regras ganhando perfil_id/
 * conta_id/usos/confirmada).
 *
 * financeiro_pessoal_categoria_aprendida() é só SELECT (SQL portável) — testada DE VERDADE
 * contra SQLite em memória, semeado via INSERT puro (não pelo upsert real).
 *
 * financeiro_pessoal_aprender_upsert() usa `ON DUPLICATE KEY UPDATE` (sintaxe só de MySQL, que o
 * SQLite não entende — mesma limitação de "não há banco de teste no projeto" já documentada em
 * CLAUDE.md e já usada em tests/marketing_sync_test.php pro mesmo motivo) — por isso sua lógica de
 * decisão (usos soma/reseta, confirmada a partir de usos>=2) é testada como réplica isolada da
 * fórmula exata do código real (copiada linha a linha de app/Helpers/functions.php), não pela
 * função de verdade.
 *
 * Rodar com: php tests/fixa_aprendizado_categoria_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Helpers/functions.php';

$falhas = 0; $total = 0;
function assert_igual($esperado, $obtido, string $descricao): void
{
    global $falhas, $total;
    $total++;
    if ($esperado === $obtido) { echo "  OK  $descricao\n"; return; }
    $falhas++;
    echo "FALHA $descricao\n      esperado: " . var_export($esperado, true) . "\n      obtido:   " . var_export($obtido, true) . "\n";
}
function assert_nulo($obtido, string $descricao): void
{
    global $falhas, $total;
    $total++;
    if ($obtido === null) { echo "  OK  $descricao\n"; return; }
    $falhas++;
    echo "FALHA $descricao\n      esperado: null\n      obtido:   " . var_export($obtido, true) . "\n";
}

function novoBanco(): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec(
        "CREATE TABLE financeiro_pessoal_categoria_regras (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            usuario_id INTEGER NOT NULL,
            perfil_id INTEGER,
            beneficiario_normalizado TEXT NOT NULL,
            categoria TEXT NOT NULL,
            conta_id INTEGER,
            usos INTEGER NOT NULL DEFAULT 1,
            confirmada INTEGER NOT NULL DEFAULT 0,
            atualizado_em TEXT DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (usuario_id, perfil_id, beneficiario_normalizado)
        )"
    );
    return $pdo;
}

/** Insere uma regra direto via SQL portável — não passa pelo upsert real (MySQL-only). */
function semearRegra(PDO $pdo, int $usuarioId, int $perfilId, string $beneficiario, string $categoria, ?int $contaId, int $usos = 1, bool $confirmada = false): void
{
    $chave = financeiro_pessoal_normalizar_beneficiario($beneficiario);
    $pdo->prepare(
        "INSERT INTO financeiro_pessoal_categoria_regras (usuario_id, perfil_id, beneficiario_normalizado, categoria, conta_id, usos, confirmada)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    )->execute([$usuarioId, $perfilId, $chave, $categoria, $contaId, $usos, $confirmada ? 1 : 0]);
}

/**
 * Réplica EXATA da fórmula de decisão de financeiro_pessoal_aprender_upsert()
 * (app/Helpers/functions.php) — mesma categoria de novo soma uso; categoria diferente reseta
 * pra 1; confirmada = usos>=2. Não reimplementa o INSERT (isso já é SQL de verdade, sem lógica
 * a testar) — só a parte decisória que o INSERT ... ON DUPLICATE KEY UPDATE grava.
 */
function replicaDecisaoUpsert(?array $atual, string $categoriaNova): array
{
    $usos = ($atual && $atual['categoria'] === $categoriaNova) ? ((int) $atual['usos'] + 1) : 1;
    $confirmada = $usos >= 2 ? 1 : 0;
    return ['usos' => $usos, 'confirmada' => $confirmada];
}

echo "== financeiro_pessoal_normalizar_beneficiario() ==\n";
{
    assert_igual('enel distribuicao sp', financeiro_pessoal_normalizar_beneficiario('ENEL DISTRIBUIÇÃO SP'), 'minúsculo + sem acento');
    assert_igual('miguel', financeiro_pessoal_normalizar_beneficiario('  Miguel  '), 'trim + espaço duplo');
    assert_igual('', financeiro_pessoal_normalizar_beneficiario(''), 'vazio continua vazio');
}

echo "\n== réplica da decisão de financeiro_pessoal_aprender_upsert() — primeira vez ==\n";
{
    $r = replicaDecisaoUpsert(null, 'moradia');
    assert_igual(1, $r['usos'], 'primeira vez: usos=1');
    assert_igual(0, $r['confirmada'], 'primeira vez: ainda não confirmada (usos<2)');
}

echo "\n== réplica — mesma categoria de novo soma uso e confirma a partir de 2 ==\n";
{
    $r1 = replicaDecisaoUpsert(['categoria' => 'moradia', 'usos' => 1], 'moradia');
    assert_igual(2, $r1['usos'], 'segunda vez, mesma categoria: usos=2');
    assert_igual(1, $r1['confirmada'], 'confirmada vira 1 a partir de usos>=2');

    $r2 = replicaDecisaoUpsert(['categoria' => 'moradia', 'usos' => 2], 'moradia');
    assert_igual(3, $r2['usos'], 'terceira vez: usos continua subindo (3)');
    assert_igual(1, $r2['confirmada'], 'continua confirmada');
}

echo "\n== réplica — categoria DIFERENTE reseta o contador ==\n";
{
    $r = replicaDecisaoUpsert(['categoria' => 'moradia', 'usos' => 2], 'outros');
    assert_igual(1, $r['usos'], 'usos volta pra 1 (recomeça a contar)');
    assert_igual(0, $r['confirmada'], 'confirmada volta pra 0');
}

echo "\n== financeiro_pessoal_aprender_upsert() — guard de entrada vazia (continua chamável sem erro) ==\n";
{
    $pdo = novoBanco();
    // beneficiário vazio / categoria vazia: a função real retorna antes de tocar no banco —
    // isso não usa ON DUPLICATE KEY UPDATE (é um "return;" simples), então dá pra chamar a
    // função de VERDADE aqui sem cair na incompatibilidade de sintaxe do SQLite.
    financeiro_pessoal_aprender_upsert($pdo, 1, 10, '', 'moradia', null);
    financeiro_pessoal_aprender_upsert($pdo, 1, 10, 'Mercado', '', null);
    $qtd = (int) $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_categoria_regras")->fetchColumn();
    assert_igual(0, $qtd, 'nem descrição vazia nem categoria vazia tentam gravar nada (guard roda antes do SQL)');
}

echo "\n== financeiro_pessoal_categoria_aprendida() ==\n";
{
    $pdo = novoBanco();
    assert_nulo(financeiro_pessoal_categoria_aprendida($pdo, 1, 10, 'Enel'), 'sem regra nenhuma: null');

    semearRegra($pdo, 1, 10, 'Enel Distribuição SP', 'moradia', 7, 1, false);
    $r = financeiro_pessoal_categoria_aprendida($pdo, 1, 10, 'ENEL DISTRIBUIÇÃO SP'); // grafia diferente, mesmo normalizado
    assert_igual('moradia', $r['categoria'], 'acha mesmo com maiúscula/acento diferente (normalização)');
    assert_igual(7, $r['conta_id'], 'devolve a conta sugerida junto');
    assert_igual(1, $r['usos'], 'devolve o contador de usos');
    assert_igual(false, $r['confirmada'], 'ainda não confirmada com 1 uso só');
}

echo "\n== financeiro_pessoal_categoria_aprendida() — isolado por PERFIL (mesmo usuário, perfis diferentes) ==\n";
{
    $pdo = novoBanco();
    semearRegra($pdo, 1, 10, 'Vivo', 'assinaturas', null);
    semearRegra($pdo, 1, 20, 'Vivo', 'telefonia_pj', null); // outro perfil do MESMO usuário

    $r10 = financeiro_pessoal_categoria_aprendida($pdo, 1, 10, 'Vivo');
    $r20 = financeiro_pessoal_categoria_aprendida($pdo, 1, 20, 'Vivo');
    assert_igual('assinaturas', $r10['categoria'], 'perfil 10 mantém a própria categoria');
    assert_igual('telefonia_pj', $r20['categoria'], 'perfil 20 mantém a categoria diferente, sem contaminar o outro');
}

echo "\n------------------------------------------------------------\n";
echo "$total verificações, $falhas falha(s).\n";
exit($falhas > 0 ? 1 : 0);
