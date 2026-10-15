<?php
/*
 * Previsão financeira do Carteira Fixa (App\Services\Fixa\PrevisaoService) — testa a classe DE
 * VERDADE (sem reimplementar nada) contra SQLite em memória: saldo projetado só com lançamentos
 * já gravados, simulação de ocorrência futura de recorrente SEM data_fim ainda não materializada
 * (sem duplicar o que já foi gravado por RecorrenteService::gerarPendentes()), recorrente COM
 * data_fim nunca é simulada (já é materializada de uma vez na criação), transferência nunca
 * entra na soma, caixinha desconta o saldo atual mas nunca vira item da lista, dia com saldo
 * negativo é sinalizado, perfil sem nenhum lançamento marca "incompleta", e agregação simples de
 * "próximas faturas" por forma de pagamento tipo cartão.
 *
 * Rodar com: php tests/fixa_previsao_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Helpers/functions.php';
require BASE_PATH . '/app/Services/Fixa/CaixinhaService.php';
require BASE_PATH . '/app/Services/Fixa/FormaPagamentoService.php';
require BASE_PATH . '/app/Services/Fixa/RecorrenteService.php';
require BASE_PATH . '/app/Services/Fixa/PrevisaoService.php';

use App\Services\Fixa\PrevisaoService;
use App\Services\Fixa\RecorrenteService;

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
        nome TEXT NOT NULL, tipo TEXT DEFAULT 'corrente', saldo_inicial REAL DEFAULT 0,
        data_saldo_inicial TEXT, cor TEXT, arquivada INTEGER DEFAULT 0, padrao INTEGER DEFAULT 0
    )");
    $pdo->exec("CREATE TABLE financeiro_pessoal_formas_pagamento (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER NOT NULL,
        conta_id INTEGER, nome TEXT NOT NULL, tipo TEXT DEFAULT 'simples', direcao TEXT DEFAULT 'ambas',
        dia_fechamento INTEGER, dia_vencimento INTEGER, prazo_dias INTEGER DEFAULT 30,
        cor TEXT, icone TEXT, arquivada INTEGER DEFAULT 0
    )");
    $pdo->exec("CREATE TABLE financeiro_pessoal_recorrentes (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER NOT NULL,
        conta_id INTEGER, forma_pagamento_id INTEGER, tipo TEXT DEFAULT 'despesa', categoria TEXT DEFAULT 'outros',
        descricao TEXT NOT NULL, notas TEXT, valor REAL NOT NULL, dia_vencimento INTEGER NOT NULL,
        data_inicio TEXT, data_fim TEXT, ativo INTEGER DEFAULT 1, criado_em TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE financeiro_pessoal_lancamentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER,
        conta_id INTEGER, forma_pagamento_id INTEGER, recorrente_id INTEGER, tipo TEXT DEFAULT 'despesa', categoria TEXT,
        descricao TEXT NOT NULL, observacao TEXT, valor REAL NOT NULL, data_hora TEXT NOT NULL,
        data_evento TEXT, vencimento TEXT, pago_em TEXT, hora_informada INTEGER DEFAULT 1, origem TEXT DEFAULT 'manual'
    )");
    $pdo->exec("CREATE TABLE caixinhas (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER NOT NULL, perfil_id INTEGER NOT NULL,
        nome TEXT NOT NULL, cor TEXT, icone TEXT, meta_centavos INTEGER, data_meta TEXT,
        arquivada INTEGER DEFAULT 0, created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    $pdo->exec("CREATE TABLE caixinha_movimentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT, caixinha_id INTEGER NOT NULL, usuario_id INTEGER NOT NULL,
        perfil_id INTEGER NOT NULL, tipo TEXT NOT NULL, valor_centavos INTEGER NOT NULL, data TEXT NOT NULL,
        conta_id INTEGER, transferido_banco INTEGER DEFAULT 0, observacao TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )");
    return $pdo;
}

/** Soma todos os itens de todos os dias da projeção que batem um filtro simples — evita repetir
 *  o mesmo loop em cada bloco de teste abaixo. */
function itensAchados(array $projecao, callable $filtro): array
{
    $out = [];
    foreach ($projecao['dias'] as $dia) {
        foreach ($dia['itens'] as $it) {
            if ($filtro($it, $dia['data'])) { $out[] = $it; }
        }
    }
    return $out;
}

$hoje = date('Y-m-d');

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "== projetar() — só lançamentos reais, dentro de 7 dias ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, saldo_inicial, data_saldo_inicial) VALUES (100, 1, 10, 'Carteira', 1000, '2026-01-01')");

    $venc5 = date('Y-m-d', strtotime($hoje . ' +5 days'));
    $venc3 = date('Y-m-d', strtotime($hoje . ' +3 days'));
    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (usuario_id, perfil_id, tipo, categoria, descricao, valor, data_hora, vencimento) VALUES (1, 10, 'receita', 'salario', 'Salário', 500, '$venc3 00:00:00', '$venc3')");
    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (usuario_id, perfil_id, tipo, categoria, descricao, valor, data_hora, vencimento) VALUES (1, 10, 'despesa', 'moradia', 'Aluguel', 200, '$venc5 00:00:00', '$venc5')");
    // Fora da janela de 7 dias — não deve entrar na soma.
    $forada = date('Y-m-d', strtotime($hoje . ' +20 days'));
    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (usuario_id, perfil_id, tipo, categoria, descricao, valor, data_hora, vencimento) VALUES (1, 10, 'despesa', 'outros', 'Fora da janela', 9999, '$forada 00:00:00', '$forada')");

    $dataFim = date('Y-m-d', strtotime($hoje . ' +6 days'));
    $p = PrevisaoService::projetar($pdo, 1, 10, $hoje, $dataFim);

    assert_igual(1000.0, $p['saldoAtual'], 'saldo atual = só saldo_inicial (nada pago ainda)');
    assert_igual(1300.0, $p['saldoFinal'], 'saldo final = 1000 + 500 (receita) - 200 (despesa), ignora o que está fora da janela');
    assert_igual(false, $p['incompleta'], 'não está incompleta, tem lançamento');
    $achouForaDaJanela = itensAchados($p, fn($it) => $it['descricao'] === 'Fora da janela');
    assert_igual(0, count($achouForaDaJanela), 'lançamento com vencimento fora da janela de 7 dias não aparece');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== projetar() — recorrente SEM data_fim, ocorrência ainda não materializada é simulada ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, saldo_inicial, data_saldo_inicial) VALUES (100, 1, 10, 'Carteira', 0, '2026-01-01')");
    $pdo->exec("INSERT INTO financeiro_pessoal_recorrentes (id, usuario_id, perfil_id, tipo, categoria, descricao, valor, dia_vencimento, data_inicio, data_fim) VALUES (1, 1, 10, 'despesa', 'assinatura', 'Streaming', 30, 15, NULL, NULL)");

    // Janela de ~95 dias — cobre pelo menos mês atual + 2 seguintes, sem nenhum gerarPendentes()
    // ter rodado (RecorrenteService::gerarPendentes() nunca é chamado por projetar() — a
    // classe só lê/simula). Calcula as datas esperadas do mesmo jeito que a classe real faz,
    // reaproveitando dataVencimentoNoMes(), pra não depender de uma data fixa no calendário.
    $dataFim = date('Y-m-d', strtotime($hoje . ' +95 days'));
    $mesCursor = date('Y-m');
    $mesFim = substr($dataFim, 0, 7);
    $esperadas = [];
    while ($mesCursor <= $mesFim) {
        $d = RecorrenteService::dataVencimentoNoMes(15, $mesCursor);
        if ($d >= $hoje && $d <= $dataFim) { $esperadas[] = $d; }
        $mesCursor = date('Y-m', strtotime($mesCursor . '-01 +1 month'));
    }
    assert_verdadeiro(count($esperadas) >= 2, 'o cenário de teste cobre pelo menos 2 ocorrências futuras (janela de 95 dias)');

    $p = PrevisaoService::projetar($pdo, 1, 10, $hoje, $dataFim);
    $itensStreaming = itensAchados($p, fn($it) => $it['descricao'] === 'Streaming');

    assert_igual(count($esperadas), count($itensStreaming), 'uma ocorrência simulada por mês dentro da janela, nenhuma duplicada nem faltando');
    foreach ($itensStreaming as $it) {
        assert_igual('simulado', $it['origem'], 'ocorrência simulada vem marcada como "simulado" (nunca gravada no banco)');
    }
    assert_igual(0, (int) $pdo->query("SELECT COUNT(*) FROM financeiro_pessoal_lancamentos")->fetchColumn(), 'projetar() nunca grava nada — tabela de lançamentos continua vazia');
    assert_igual(-30.0 * count($esperadas), $p['saldoFinal'], 'saldo final desconta todas as ocorrências simuladas');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== projetar() — ocorrência JÁ materializada nunca é simulada de novo (sem duplicar) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, saldo_inicial, data_saldo_inicial) VALUES (100, 1, 10, 'Carteira', 0, '2026-01-01')");
    $pdo->exec("INSERT INTO financeiro_pessoal_recorrentes (id, usuario_id, perfil_id, tipo, categoria, descricao, valor, dia_vencimento, data_inicio, data_fim) VALUES (1, 1, 10, 'despesa', 'teste', 'Academia', 80, 28, NULL, NULL)");

    $dataFim = date('Y-m-d', strtotime($hoje . ' +40 days'));
    $mesCursor = date('Y-m');
    $mesFim = substr($dataFim, 0, 7);
    $esperadas = [];
    while ($mesCursor <= $mesFim) {
        $d = RecorrenteService::dataVencimentoNoMes(28, $mesCursor);
        if ($d >= $hoje && $d <= $dataFim) { $esperadas[] = $d; }
        $mesCursor = date('Y-m', strtotime($mesCursor . '-01 +1 month'));
    }
    assert_verdadeiro(count($esperadas) >= 1, 'o cenário de teste cobre pelo menos 1 ocorrência (janela de 40 dias, dia 28)');

    // Simula que RecorrenteService::gerarPendentes() já rodou e gravou a PRIMEIRA ocorrência —
    // mesmo formato de linha que ele grava de verdade (recorrente_id + vencimento, sem forma).
    $primeira = $esperadas[0];
    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (usuario_id, perfil_id, recorrente_id, tipo, categoria, descricao, valor, data_hora, vencimento) VALUES (1, 10, 1, 'despesa', 'teste', 'Academia', 80, '$primeira 00:00:00', '$primeira')");

    $p = PrevisaoService::projetar($pdo, 1, 10, $hoje, $dataFim);
    $itensAcademia = itensAchados($p, fn($it) => $it['descricao'] === 'Academia');

    assert_igual(count($esperadas), count($itensAcademia), 'total de ocorrências bate (real + simuladas), sem duplicar a que já foi materializada');
    $reais = array_filter($itensAcademia, fn($it) => $it['origem'] === 'real');
    $simuladas = array_filter($itensAcademia, fn($it) => $it['origem'] === 'simulado');
    assert_igual(1, count($reais), 'exatamente 1 ocorrência "real" — a que já estava gravada');
    assert_igual(count($esperadas) - 1, count($simuladas), 'as demais ocorrências (meses seguintes) continuam simuladas normalmente');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== projetar() — recorrente COM data_fim nunca é simulada (já materializada de uma vez) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, saldo_inicial, data_saldo_inicial) VALUES (100, 1, 10, 'Carteira', 0, '2026-01-01')");
    $dataFimRecorrente = date('Y-m-d', strtotime($hoje . ' +10 days'));
    // COM data_fim preenchido, mas de propósito SEM nenhum lançamento materializado pra ela —
    // simula o caso em que RecorrenteService::gerarPendentes() ainda não rodou (não é
    // responsabilidade de PrevisaoService disparar isso).
    $pdo->exec("INSERT INTO financeiro_pessoal_recorrentes (id, usuario_id, perfil_id, tipo, categoria, descricao, valor, dia_vencimento, data_inicio, data_fim)
                VALUES (1, 1, 10, 'despesa', 'parcelamento', 'Geladeira 3x', 200, 10, '$hoje', '$dataFimRecorrente')");

    $dataFim = date('Y-m-d', strtotime($hoje . ' +90 days'));
    $p = PrevisaoService::projetar($pdo, 1, 10, $hoje, $dataFim);
    $itensGeladeira = itensAchados($p, fn($it) => $it['descricao'] === 'Geladeira 3x');

    assert_igual(0, count($itensGeladeira), 'recorrente com data_fim nunca é simulada — fica a cargo de gerarPendentes() materializar de verdade');
    assert_igual(0.0, $p['saldoFinal'], 'saldo final não é afetado por uma recorrente com data_fim não materializada');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== projetar() — transferência nunca entra na soma ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, saldo_inicial, data_saldo_inicial) VALUES (100, 1, 10, 'Carteira', 500, '2026-01-01')");
    $venc = date('Y-m-d', strtotime($hoje . ' +2 days'));
    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (usuario_id, perfil_id, tipo, categoria, descricao, valor, data_hora, vencimento) VALUES (1, 10, 'transferencia', 'outros', 'Transferência entre contas', 9999, '$venc 00:00:00', '$venc')");

    $dataFim = date('Y-m-d', strtotime($hoje . ' +6 days'));
    $p = PrevisaoService::projetar($pdo, 1, 10, $hoje, $dataFim);

    assert_igual(500.0, $p['saldoFinal'], 'transferência não muda o saldo projetado');
    $achouTransferencia = itensAchados($p, fn($it) => $it['descricao'] === 'Transferência entre contas');
    assert_igual(0, count($achouTransferencia), 'transferência nunca aparece na lista de itens do dia');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== projetar() — caixinha desconta o saldo atual, nunca vira item da lista ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, saldo_inicial, data_saldo_inicial) VALUES (100, 1, 10, 'Carteira', 1000, '2026-01-01')");
    $pdo->exec("INSERT INTO caixinhas (id, usuario_id, perfil_id, nome) VALUES (1, 1, 10, 'Viagem')");
    $pdo->exec("INSERT INTO caixinha_movimentos (caixinha_id, usuario_id, perfil_id, tipo, valor_centavos, data) VALUES (1, 1, 10, 'deposito', 30000, '$hoje')"); // R$300

    $dataFim = date('Y-m-d', strtotime($hoje . ' +6 days'));
    $p = PrevisaoService::projetar($pdo, 1, 10, $hoje, $dataFim);

    assert_igual(700.0, $p['saldoAtual'], 'R$300 guardados na caixinha saem do saldo atual (1000 - 300)');
    assert_igual(700.0, $p['saldoFinal'], 'sem nenhum lançamento futuro, saldo final = saldo atual');
    $achouCaixinha = itensAchados($p, fn($it) => str_contains(strtolower((string) $it['descricao']), 'viagem'));
    assert_igual(0, count($achouCaixinha), 'movimento de caixinha nunca vira item de dia da previsão');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== projetar() — dia com saldo projetado negativo é sinalizado ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, saldo_inicial, data_saldo_inicial) VALUES (100, 1, 10, 'Carteira', 100, '2026-01-01')");
    $venc = date('Y-m-d', strtotime($hoje . ' +4 days'));
    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (usuario_id, perfil_id, tipo, categoria, descricao, valor, data_hora, vencimento) VALUES (1, 10, 'despesa', 'outros', 'Conta grande', 500, '$venc 00:00:00', '$venc')");

    $dataFim = date('Y-m-d', strtotime($hoje . ' +6 days'));
    $p = PrevisaoService::projetar($pdo, 1, 10, $hoje, $dataFim);

    assert_igual(-400.0, $p['saldoFinal'], 'saldo final fica negativo (100 - 500)');
    // Uma vez negativo, o saldo acumulado continua negativo em todo dia seguinte da janela
    // (nada traz ele de volta pra cima neste cenário) — a lista esperada é do dia da despesa
    // até o fim da janela, não só o dia exato em que a despesa caiu.
    $esperadosNegativos = [];
    for ($d = $venc; $d <= $dataFim; $d = date('Y-m-d', strtotime($d . ' +1 day'))) { $esperadosNegativos[] = $d; }
    assert_igual($esperadosNegativos, $p['diasNegativos'], 'do dia da despesa grande até o fim da janela, todos ficam marcados como negativos (saldo acumulado nunca volta sozinho)');
    $diaAlvo = null;
    foreach ($p['dias'] as $d) { if ($d['data'] === $venc) { $diaAlvo = $d; } }
    assert_verdadeiro($diaAlvo !== null && $diaAlvo['negativo'] === true, 'o próprio dia vem com a flag negativo=true');
    // Dias ANTES da despesa continuam positivos (saldo só vira negativo a partir do dia em questão).
    $diaAnterior = null;
    foreach ($p['dias'] as $d) { if ($d['data'] === $hoje) { $diaAnterior = $d; } }
    assert_verdadeiro($diaAnterior !== null && $diaAnterior['negativo'] === false, 'hoje (antes da despesa) continua positivo');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== projetar() — perfil sem nenhum lançamento marca 'incompleta' ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, saldo_inicial, data_saldo_inicial) VALUES (100, 1, 10, 'Carteira', 0, '2026-01-01')");

    $dataFim = date('Y-m-d', strtotime($hoje . ' +29 days'));
    $p = PrevisaoService::projetar($pdo, 1, 10, $hoje, $dataFim);

    assert_igual(true, $p['incompleta'], 'perfil sem nenhum lançamento/recorrente marca previsão incompleta');
    assert_igual(0.0, $p['saldoFinal'], 'saldo final = saldo atual (0), sem nenhum movimento');
    assert_igual(0, count($p['diasNegativos']), 'nenhum dia negativo (não tem nada lançado)');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== projetar() — próximas faturas agrega parcelas da mesma forma + vencimento ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo = novoBanco();
    $pdo->exec("INSERT INTO financeiro_pessoal_contas (id, usuario_id, perfil_id, nome, saldo_inicial, data_saldo_inicial) VALUES (100, 1, 10, 'Carteira', 1000, '2026-01-01')");
    $pdo->exec("INSERT INTO financeiro_pessoal_formas_pagamento (id, usuario_id, perfil_id, nome, tipo, dia_fechamento, dia_vencimento, cor, icone) VALUES (1, 1, 10, 'Nubank', 'cartao', 5, 12, '#820AD1', 'credit-card-2-front-fill')");

    $venc = date('Y-m-d', strtotime($hoje . ' +8 days'));
    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (usuario_id, perfil_id, forma_pagamento_id, tipo, categoria, descricao, valor, data_hora, vencimento) VALUES (1, 10, 1, 'despesa', 'compras', 'Parcela 1/2 — Notebook', 400, '$hoje 00:00:00', '$venc')");
    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (usuario_id, perfil_id, forma_pagamento_id, tipo, categoria, descricao, valor, data_hora, vencimento) VALUES (1, 10, 1, 'despesa', 'compras', 'Mercado', 120, '$hoje 00:00:00', '$venc')");
    // Forma 'simples' (não cartão) com vencimento na mesma data — não deve entrar na agregação de fatura.
    $pdo->exec("INSERT INTO financeiro_pessoal_formas_pagamento (id, usuario_id, perfil_id, nome, tipo) VALUES (2, 1, 10, 'Pix', 'simples')");
    $pdo->exec("INSERT INTO financeiro_pessoal_lancamentos (usuario_id, perfil_id, forma_pagamento_id, tipo, categoria, descricao, valor, data_hora, vencimento) VALUES (1, 10, 2, 'despesa', 'outros', 'Pix avulso', 50, '$hoje 00:00:00', '$venc')");

    $dataFim = date('Y-m-d', strtotime($hoje . ' +15 days'));
    $p = PrevisaoService::projetar($pdo, 1, 10, $hoje, $dataFim);

    assert_igual(1, count($p['proximasFaturas']), 'só 1 fatura agregada (a da forma tipo cartão)');
    assert_igual('Nubank', $p['proximasFaturas'][0]['forma_nome'], 'nome da forma certo');
    assert_igual(520.0, $p['proximasFaturas'][0]['total'], 'soma as 2 parcelas da mesma fatura (400 + 120), ignora o Pix avulso');
    assert_igual(2, $p['proximasFaturas'][0]['qtd'], 'conta 2 lançamentos nessa fatura');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n==================================================\n";
echo "$total verificações, $falhas falha(s)\n";
if ($falhas > 0) { exit(1); }
echo "Tudo certo.\n";
