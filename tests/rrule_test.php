<?php
/*
 * Testes das funções puras de RRULE (app/Helpers/rrule.php) — o projeto não tem PHPUnit nem
 * Composer (ver CLAUDE.md), então isso é um runner mínimo próprio: cada bloco chama
 * assert_igual()/assert_verdadeiro() e o script termina com exit(1) se algo falhar, exit(0)
 * se tudo passar. Rodar com: php tests/rrule_test.php
 *
 * Cobre só a matemática de expansão da regra (pura, sem banco) — a parte de exceções
 * (editar/excluir ocorrência isolada) mexe com AgendaController::salvar()/excluir() e
 * banco de dados, fora do escopo deste runner (o projeto não tem fixture de banco de teste).
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Helpers/rrule.php';

$falhas = 0;
$total  = 0;

function assert_igual($esperado, $obtido, string $descricao): void
{
    global $falhas, $total;
    $total++;
    if ($esperado === $obtido) {
        echo "  OK  $descricao\n";
    } else {
        $falhas++;
        echo "FALHA $descricao\n";
        echo "      esperado: " . var_export($esperado, true) . "\n";
        echo "      obtido:   " . var_export($obtido, true) . "\n";
    }
}

function assert_verdadeiro(bool $cond, string $descricao): void
{
    global $falhas, $total;
    $total++;
    if ($cond) {
        echo "  OK  $descricao\n";
    } else {
        $falhas++;
        echo "FALHA $descricao\n";
    }
}

// ── Virada de mês ───────────────────────────────────────────────────────────
echo "-- Virada de mês --\n";
{
    // DTSTART 15/jan, janela 20/jan a 20/fev -> só a ocorrência de fevereiro deve aparecer
    // (a de janeiro é antes do início da janela).
    $r = agenda_rrule_expandir('2026-01-15 10:00:00', 'FREQ=MONTHLY', '2026-01-20', '2026-02-20');
    assert_igual(['2026-02-15 10:00:00'], $r, 'mensal: janela que corta o mês de início só traz a ocorrência seguinte');

    // Semanal atravessando virada de mês (toda quinta, de 28/jan a 15/fev/2026).
    // 2026-01-15 é quinta; quintas seguintes: 22/01, 29/01, 05/02, 12/02.
    $r = agenda_rrule_expandir('2026-01-15 09:00:00', 'FREQ=WEEKLY', '2026-01-28', '2026-02-15');
    assert_igual(['2026-01-29 09:00:00', '2026-02-05 09:00:00', '2026-02-12 09:00:00'], $r,
        'semanal: ocorrências corretas atravessando virada de mês');
}

// ── Dia 31 em meses curtos (e 29/fev em ano não bissexto) ──────────────────
echo "-- Dia 31 em meses curtos / 29 de fevereiro --\n";
{
    // DTSTART 31/jan, mensal -> só ocorre em meses com 31 dias (jan, mar, mai...), NUNCA
    // "escorrega" pro dia 1 do mês seguinte nem clampa pro último dia do mês curto.
    $r = agenda_rrule_expandir('2026-01-31 08:00:00', 'FREQ=MONTHLY', '2026-01-01', '2026-06-30');
    assert_igual([
        '2026-01-31 08:00:00',
        '2026-03-31 08:00:00',
        '2026-05-31 08:00:00',
    ], $r, 'mensal dia 31: pula fevereiro/abril/junho (30 ou 28 dias), sem clampar nem rolar');

    // 29/fev anual: só existe em ano bissexto.
    $r = agenda_rrule_expandir('2024-02-29 12:00:00', 'FREQ=YEARLY', '2024-01-01', '2029-12-31');
    assert_igual(['2024-02-29 12:00:00', '2028-02-29 12:00:00'], $r,
        'anual 29/fev: só em anos bissextos (2024 e 2028), pula 2025/26/27/29');

    // COUNT deve contar só ocorrências que de fato existem (não os meses pulados).
    $r = agenda_rrule_expandir('2026-01-31 08:00:00', 'FREQ=MONTHLY;COUNT=3', '2026-01-01', '2026-12-31');
    assert_igual(['2026-01-31 08:00:00', '2026-03-31 08:00:00', '2026-05-31 08:00:00'], $r,
        'mensal dia 31 com COUNT=3: conta 3 ocorrências reais (jan/mar/mai), não 3 meses');
}

// ── Fuso America/Sao_Paulo, incluindo horário de verão histórico ───────────
echo "-- Fuso America/Sao_Paulo (horário de verão histórico) --\n";
{
    // DTSTART antes do início do horário de verão 2018/2019 (que começou em 04/11/2018 e
    // terminou em 17/02/2019). Mensal, todo dia 15 às 14:00 -- o horário LOCAL (wall-clock)
    // tem que continuar 14:00:00 em toda ocorrência, mesmo cruzando a virada de/para o
    // horário de verão (quando o offset UTC muda de -03:00 pra -02:00 e volta).
    $r = agenda_rrule_expandir('2018-10-15 14:00:00', 'FREQ=MONTHLY', '2018-10-01', '2019-03-31');
    $esperado = [
        '2018-10-15 14:00:00', // antes do horário de verão (-03:00)
        '2018-11-15 14:00:00', // já em horário de verão (-02:00)
        '2018-12-15 14:00:00', // horário de verão
        '2019-01-15 14:00:00', // horário de verão
        '2019-02-15 14:00:00', // horário de verão (termina 17/02/2019)
        '2019-03-15 14:00:00', // já fora do horário de verão (-03:00 de novo)
    ];
    assert_igual($esperado, $r, 'horário local (14:00) estável em toda ocorrência, atravessando DST 2018/2019');

    // Confere o offset UTC real de duas ocorrências pra provar que o DST realmente mudou
    // por baixo (senão o teste acima poderia estar "passando" só por acidente/timezone
    // errado no ambiente).
    $tz = new DateTimeZone('America/Sao_Paulo');
    $antes = new DateTime('2018-10-15 14:00:00', $tz);
    $depois = new DateTime('2018-11-15 14:00:00', $tz);
    assert_igual('-03:00', $antes->format('P'), 'offset de outubro/2018 é -03:00 (fora do DST)');
    assert_igual('-02:00', $depois->format('P'), 'offset de novembro/2018 é -02:00 (dentro do DST)');

    // Semanal também não pode derivar: toda segunda às 09:30, atravessando a mesma virada.
    $r = agenda_rrule_expandir('2018-10-08 09:30:00', 'FREQ=WEEKLY', '2018-10-08', '2018-11-19');
    foreach ($r as $ocorrencia) {
        assert_verdadeiro(str_ends_with($ocorrencia, ' 09:30:00'), "semanal mantém 09:30:00 em $ocorrencia");
    }
}

// ── Frequências básicas / INTERVAL / mensal por posição ────────────────────
echo "-- Básico: DAILY/WEEKLY/INTERVAL/mensal por posição --\n";
{
    $r = agenda_rrule_expandir('2026-08-01 08:00:00', 'FREQ=DAILY', '2026-08-01', '2026-08-05');
    assert_igual([
        '2026-08-01 08:00:00', '2026-08-02 08:00:00', '2026-08-03 08:00:00',
        '2026-08-04 08:00:00', '2026-08-05 08:00:00',
    ], $r, 'diária simples');

    $r = agenda_rrule_expandir('2026-08-01 08:00:00', 'FREQ=DAILY;INTERVAL=3', '2026-08-01', '2026-08-10');
    assert_igual(['2026-08-01 08:00:00', '2026-08-04 08:00:00', '2026-08-07 08:00:00', '2026-08-10 08:00:00'], $r,
        'diária a cada 3 dias');

    // Agosto/2026: dia 1 é sábado -> segundas-feiras são 3, 10, 17, 24, 31.
    $r = agenda_rrule_expandir('2026-08-03 10:00:00', 'FREQ=MONTHLY;BYDAY=1MO', '2026-08-01', '2026-10-31');
    assert_igual(['2026-08-03 10:00:00', '2026-09-07 10:00:00', '2026-10-05 10:00:00'], $r,
        'mensal por posição: toda 1ª segunda-feira do mês');

    // Última sexta-feira do mês, ago-out/2026.
    $r = agenda_rrule_expandir('2026-08-28 10:00:00', 'FREQ=MONTHLY;BYDAY=-1FR', '2026-08-01', '2026-10-31');
    assert_igual(['2026-08-28 10:00:00', '2026-09-25 10:00:00', '2026-10-30 10:00:00'], $r,
        'mensal por posição: toda última sexta-feira do mês');
}

// ── UNTIL / COUNT ────────────────────────────────────────────────────────
echo "-- Término (UNTIL / COUNT) --\n";
{
    $r = agenda_rrule_expandir('2026-08-01 08:00:00', 'FREQ=WEEKLY;UNTIL=20260822', '2026-08-01', '2026-12-31');
    assert_igual(['2026-08-01 08:00:00', '2026-08-08 08:00:00', '2026-08-15 08:00:00', '2026-08-22 08:00:00'], $r,
        'UNTIL inclui o próprio dia limite e para depois dele');

    $r = agenda_rrule_expandir('2026-08-01 08:00:00', 'FREQ=WEEKLY;COUNT=2', '2026-08-01', '2026-12-31');
    assert_igual(['2026-08-01 08:00:00', '2026-08-08 08:00:00'], $r, 'COUNT=2 gera exatamente 2 ocorrências');

    // COUNT conta desde o DTSTART mesmo que a janela pedida comece depois (só a 2ª ocorrência,
    // 08/08, cai dentro da janela — a 1ª, 01/08, é anterior ao início pedido).
    $r = agenda_rrule_expandir('2026-08-01 08:00:00', 'FREQ=WEEKLY;COUNT=2', '2026-08-05', '2026-12-31');
    assert_igual(['2026-08-08 08:00:00'], $r, 'COUNT conta desde o DTSTART, janela só filtra o que exibir');

    // Janela inteiramente depois do fim da série (COUNT já esgotado antes da janela começar).
    $r = agenda_rrule_expandir('2026-08-01 08:00:00', 'FREQ=WEEKLY;COUNT=2', '2026-09-01', '2026-12-31');
    assert_igual([], $r, 'COUNT=2 já esgotado antes do início da janela pedida -> nada retorna');
}

// ── montar()/parse()/descricao() ────────────────────────────────────────────
echo "-- montar() / parse() / descrição legível --\n";
{
    $rrule = agenda_rrule_montar([
        'repetir' => '1', 'repetir_freq' => 'mensal_dia', 'repetir_termino' => 'nunca',
    ], '2026-08-10 09:00:00');
    assert_igual('FREQ=MONTHLY', $rrule, 'montar(): mensal por dia do mês, sem término');
    assert_igual('Todo dia 10, mensalmente', agenda_rrule_descricao($rrule, '2026-08-10 09:00:00'),
        'descrição: "Parcela AP" (todo dia 10, mensalmente)');

    $rrule = agenda_rrule_montar([
        'repetir' => '1', 'repetir_freq' => 'mensal_dia', 'repetir_intervalo' => '1', 'repetir_termino' => 'nunca',
    ], '2026-08-05 09:00:00');
    assert_igual('Todo dia 5, mensalmente', agenda_rrule_descricao($rrule, '2026-08-05 09:00:00'),
        'descrição: "Aluguel" (todo dia 5, mensalmente)');

    $rrule = agenda_rrule_montar([
        'repetir' => '1', 'repetir_freq' => 'semanal', 'repetir_termino' => 'apos', 'repetir_count' => '10',
    ], '2026-08-10 09:00:00'); // 10/08/2026 é uma segunda-feira
    assert_igual('FREQ=WEEKLY;COUNT=10', $rrule, 'montar(): semanal com término após N ocorrências');
    assert_igual('Toda segunda-feira, por 10 vezes', agenda_rrule_descricao($rrule, '2026-08-10 09:00:00'),
        'descrição: semanal com contagem');

    $rrule = agenda_rrule_montar([
        'repetir' => '1', 'repetir_freq' => 'mensal_posicao', 'repetir_termino' => 'ate', 'repetir_until' => '2027-01-31',
    ], '2026-08-28 09:00:00'); // última sexta de agosto/2026
    assert_igual('FREQ=MONTHLY;BYDAY=-1FR;UNTIL=20270131', $rrule, 'montar(): mensal por posição com UNTIL');
    assert_igual('Toda última sexta-feira do mês, até 31/01/2027', agenda_rrule_descricao($rrule, '2026-08-28 09:00:00'),
        'descrição: mensal por posição com término até data');

    assert_igual(null, agenda_rrule_montar(['repetir_freq' => 'diaria'], '2026-08-10 09:00:00'),
        'montar(): sem "repetir" marcado retorna null (evento não recorrente)');
}

echo "\n$total verificações, $falhas falha(s).\n";
exit($falhas > 0 ? 1 : 0);
