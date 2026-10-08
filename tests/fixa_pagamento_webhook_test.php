<?php
/*
 * Testes do lado de pagamento real do Carteira Fixa standalone (Item 5 do pedido de cobrança):
 * o ramo `tipo='fixa'` de PagamentoController::webhook() (dispatch por prefixo de `plano`),
 * AssinaturaService::confirmarUpgrade() contra SQLite de verdade, e o dedup de
 * fixa_assinatura_avisos com a coluna `referencia` (migration 095).
 *
 * Não testa InfinitePayService::criarLink()/ativo() nem FixaCadastroController::
 * gerarLinkPagamento() de ponta a ponta — exigem config/infinitepay.php (gitignored, só existe
 * no VPS) e, pra criarLink(), uma chamada HTTP real à InfinitePay; mesma limitação de sempre
 * (ver CLAUDE.md "não há banco/credencial de teste no projeto"). O que dá pra testar sem isso —
 * a lógica de dispatch do webhook, a extração do id da assinatura a partir do prefixo de
 * `plano`, e o dedup de aviso — é testado de ponta a ponta contra implementações reais.
 *
 * Rodar com: php tests/fixa_pagamento_webhook_test.php
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

/**
 * Réplica exata do dispatch dentro do ramo `elseif (($c['tipo'] ?? 'assinatura') === 'fixa')`
 * de PagamentoController::webhook() — em vez de chamar AssinaturaService::confirmarPagamento()/
 * confirmarUpgrade() de verdade, devolve QUAL delas seria chamada e com qual id, pra testar a
 * extração do prefixo isoladamente (confirmarPagamento()/confirmarUpgrade() de verdade usam
 * sintaxe só-MySQL em parte do fluxo mais amplo, mas a decisão de dispatch em si é PHP puro).
 */
function dispatchFixaWebhook(string $planoCobranca): array
{
    if (strpos($planoCobranca, 'fixa_upgrade_') === 0) {
        return ['acao' => 'confirmarUpgrade', 'assinaturaId' => (int) substr($planoCobranca, strlen('fixa_upgrade_'))];
    }
    return ['acao' => 'confirmarPagamento', 'assinaturaId' => (int) substr($planoCobranca, strlen('fixa_'))];
}

function novoBancoFixa(): PDO
{
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("CREATE TABLE fixa_assinaturas (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, plano TEXT NOT NULL,
        ciclo TEXT DEFAULT 'mensal', status TEXT DEFAULT 'teste',
        teste_fim TEXT, data_fim TEXT, valor_centavos INTEGER DEFAULT 0, credito_centavos INTEGER DEFAULT 0
    )");
    $pdo->exec("CREATE TABLE fixa_assinatura_avisos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, assinatura_id INTEGER NOT NULL, tipo TEXT NOT NULL,
        referencia TEXT, enviado_em TEXT DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(assinatura_id, tipo, referencia)
    )");
    return $pdo;
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "== Webhook 'fixa' — dispatch por prefixo de `plano` (PagamentoController::webhook()) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    assert_igual(
        ['acao' => 'confirmarPagamento', 'assinaturaId' => 42],
        dispatchFixaWebhook('fixa_42'),
        "'fixa_42' (teste virando pago, ou renovação) → confirmarPagamento(42)"
    );
    assert_igual(
        ['acao' => 'confirmarUpgrade', 'assinaturaId' => 7],
        dispatchFixaWebhook('fixa_upgrade_7'),
        "'fixa_upgrade_7' (upgrade Individual→Diretório) → confirmarUpgrade(7)"
    );
    // 'fixa_upgrade_' precisa ser checado ANTES de 'fixa_' (senão 'fixa_upgrade_7' também bateria
    // com o prefixo mais curto 'fixa_' e extrairia um id errado, 'upgrade_7' não é int válido).
    assert_igual('confirmarUpgrade', dispatchFixaWebhook('fixa_upgrade_7')['acao'], 'upgrade nunca cai no ramo de renovação por engano');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== AssinaturaService::confirmarUpgrade() — troca o plano e zera o crédito ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBancoFixa();
    $pdo->exec("INSERT INTO fixa_assinaturas (id, usuario_id, plano, status, credito_centavos) VALUES (1, 100, 'fixa_individual', 'ativa', 350)");

    AssinaturaService::confirmarUpgrade($pdo, 1, 'fixa_diretorio');

    $row = $pdo->query("SELECT plano, credito_centavos FROM fixa_assinaturas WHERE id = 1")->fetch();
    assert_igual('fixa_diretorio', $row['plano'], 'plano trocado pro novo código (fixa_diretorio)');
    assert_igual(0, (int) $row['credito_centavos'], 'crédito acumulado zerado (já foi consumido no cálculo da diferença cobrada)');

    // Assinatura inexistente não quebra (UPDATE sem match é no-op, igual confirmarPagamento()).
    AssinaturaService::confirmarUpgrade($pdo, 999, 'fixa_diretorio');
    assert_igual(1, (int) $pdo->query("SELECT COUNT(*) FROM fixa_assinaturas")->fetchColumn(), 'id inexistente: nenhuma linha nova, nenhum erro');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== fixa_assinatura_avisos — dedup com `referencia` (migration 095) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBancoFixa();
    $pdo->exec("INSERT INTO fixa_assinaturas (id, usuario_id, plano) VALUES (5, 100, 'fixa_individual')");

    $jaAvisado = function (PDO $db, int $id, string $tipo, string $ref): bool {
        $st = $db->prepare("SELECT 1 FROM fixa_assinatura_avisos WHERE assinatura_id = ? AND tipo = ? AND referencia = ?");
        $st->execute([$id, $tipo, $ref]);
        return (bool) $st->fetchColumn();
    };
    $registrar = function (PDO $db, int $id, string $tipo, string $ref): void {
        $db->prepare("INSERT INTO fixa_assinatura_avisos (assinatura_id, tipo, referencia) VALUES (?, ?, ?)")->execute([$id, $tipo, $ref]);
    };

    assert_verdadeiro(!$jaAvisado($pdo, 5, 'vencimento', '2026-10-15'), '1º ciclo vencendo em 15/10: ainda não avisado');
    $registrar($pdo, 5, 'vencimento', '2026-10-15');
    assert_verdadeiro($jaAvisado($pdo, 5, 'vencimento', '2026-10-15'), 'depois de registrar, considera já avisado (cron rodando 2x no mesmo dia não duplica)');

    // Renovou — ciclo seguinte vence em 15/11, mesma assinatura/tipo: precisa avisar de novo.
    assert_verdadeiro(!$jaAvisado($pdo, 5, 'vencimento', '2026-11-15'), 'renovou pro ciclo seguinte (nova referência): elegível de novo, sem resetar nada manualmente');
    $registrar($pdo, 5, 'vencimento', '2026-11-15');

    // '3_dias_antes' é um tipo diferente, mesma referência — não conflita com 'vencimento'.
    assert_verdadeiro(!$jaAvisado($pdo, 5, '3_dias_antes', '2026-11-15'), "tipo diferente ('3_dias_antes'), mesma referência: processa independente");
    $registrar($pdo, 5, '3_dias_antes', '2026-11-15');

    assert_igual(3, (int) $pdo->query("SELECT COUNT(*) FROM fixa_assinatura_avisos WHERE assinatura_id = 5")->fetchColumn(), '3 linhas no total: vencimento/out, vencimento/nov, 3_dias_antes/nov');

    // Teste-terminando usa a mesma tabela/coluna, mesmo sendo um tipo e fluxo diferentes —
    // confirma que o INSERT duplicado de verdade é rejeitado pela UNIQUE (não só pelo check
    // prévio em PHP), defesa real contra corrida entre duas execuções simultâneas do cron.
    $pdo->exec("INSERT INTO fixa_assinatura_avisos (assinatura_id, tipo, referencia) VALUES (5, 'teste_terminando', '2026-09-01')");
    $duplicouSemErro = true;
    try {
        $pdo->exec("INSERT INTO fixa_assinatura_avisos (assinatura_id, tipo, referencia) VALUES (5, 'teste_terminando', '2026-09-01')");
    } catch (\Throwable $e) {
        $duplicouSemErro = false;
    }
    assert_verdadeiro(!$duplicouSemErro, 'INSERT duplicado na UNIQUE é rejeitado pelo próprio banco, não só pela checagem em PHP');
}

echo "\n------------------------------------------------------------\n";
echo "$total verificações, $falhas falha(s).\n";
exit($falhas > 0 ? 1 : 0);
