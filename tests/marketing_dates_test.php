<?php
/*
 * Testes de App\Services\Marketing\Dates — "hoje" em America/Sao_Paulo perto da virada de
 * dia em UTC (regra inegociável nº5), e aritmética de calendário sem deslocar por causa de
 * horário de verão. Rodar com: php tests/marketing_dates_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Services/Marketing/Dates.php';

use App\Services\Marketing\Dates;

$falhas = 0; $total = 0;
function assert_igual($esperado, $obtido, string $descricao): void
{
    global $falhas, $total;
    $total++;
    if ($esperado === $obtido) { echo "  OK  $descricao\n"; return; }
    $falhas++;
    echo "FALHA $descricao\n      esperado: " . var_export($esperado, true) . "\n      obtido:   " . var_export($obtido, true) . "\n";
}

// ── todayInSaoPaulo perto da meia-noite UTC ─────────────────────────────────
// 2026-06-15 02:30 UTC é 2026-06-14 23:30 em São Paulo (UTC-3, sem horário de verão) —
// ainda é o dia anterior lá, mesmo já sendo o dia seguinte em UTC.
$antesDaMeiaNoiteSp = new \DateTimeImmutable('2026-06-15 02:30:00', new \DateTimeZone('UTC'));
assert_igual('2026-06-14', Dates::todayInSaoPaulo($antesDaMeiaNoiteSp), 'todayInSaoPaulo: 02:30 UTC ainda é dia anterior em SP');

$depoisDaMeiaNoiteSp = new \DateTimeImmutable('2026-06-15 03:30:00', new \DateTimeZone('UTC'));
assert_igual('2026-06-15', Dates::todayInSaoPaulo($depoisDaMeiaNoiteSp), 'todayInSaoPaulo: 03:30 UTC já virou o dia em SP');

// ── addDays / virada de mês e de ano ─────────────────────────────────────────
assert_igual('2026-03-01', Dates::addDays('2026-02-28', 1), 'addDays: 28/fev + 1 = 1/mar (2026 não é bissexto)');
assert_igual('2027-01-01', Dates::addDays('2026-12-31', 1), 'addDays: 31/dez + 1 = 1/jan do ano seguinte');
assert_igual('2026-02-27', Dates::addDays('2026-03-01', -2), 'addDays: retroceder também vira mês');
assert_igual('2026-01-01', Dates::addDays('2026-01-08', -7), 'addDays: -7 dias');

// 2028 é bissexto.
assert_igual('2028-02-29', Dates::addDays('2028-02-28', 1), 'addDays: ano bissexto tem 29/fev');
assert_igual('2028-03-01', Dates::addDays('2028-02-29', 1), 'addDays: 29/fev + 1 = 1/mar em ano bissexto');

// ── eachDay ──────────────────────────────────────────────────────────────────
assert_igual(['2026-01-01', '2026-01-02', '2026-01-03'], Dates::eachDay('2026-01-01', '2026-01-03'), 'eachDay: intervalo de 3 dias');
assert_igual(['2026-01-01'], Dates::eachDay('2026-01-01', '2026-01-01'), 'eachDay: from == to devolve 1 dia');

// ── formatDayMonth ───────────────────────────────────────────────────────────
assert_igual('05/09', Dates::formatDayMonth('2026-09-05'), 'formatDayMonth: 2026-09-05 -> 05/09');

echo "\n{$total} testes, " . ($total - $falhas) . " OK, {$falhas} falha(s)\n";
exit($falhas > 0 ? 1 : 0);
