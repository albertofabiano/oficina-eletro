<?php
/*
 * Testes de App\Services\Marketing\Money — conversão texto→centavos sem passar por float
 * (regra inegociável nº4 do módulo Marketing) e formatação em R$ pt-BR.
 * Rodar com: php tests/marketing_money_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Services/Marketing/Money.php';

use App\Services\Marketing\Money;

$falhas = 0; $total = 0;
function assert_igual($esperado, $obtido, string $descricao): void
{
    global $falhas, $total;
    $total++;
    if ($esperado === $obtido) { echo "  OK  $descricao\n"; return; }
    $falhas++;
    echo "FALHA $descricao\n      esperado: " . var_export($esperado, true) . "\n      obtido:   " . var_export($obtido, true) . "\n";
}
function assert_lanca(callable $fn, string $descricao): void
{
    global $falhas, $total;
    $total++;
    try { $fn(); $falhas++; echo "FALHA $descricao (esperava exceção, não lançou)\n"; }
    catch (\Throwable) { echo "  OK  $descricao\n"; }
}

// ── decimalToCents ──────────────────────────────────────────────────────────
assert_igual(3789, Money::decimalToCents('37.89'), 'decimalToCents("37.89") = 3789');
assert_igual(700, Money::decimalToCents('7'), 'decimalToCents("7") = 700 (inteiro)');
assert_igual(11, Money::decimalToCents('0.105'), 'decimalToCents("0.105") = 11 (arredonda pra cima no 3º decimal)');
assert_igual(10, Money::decimalToCents('0.104'), 'decimalToCents("0.104") = 10 (arredonda pra baixo)');
assert_igual(0, Money::decimalToCents('0'), 'decimalToCents("0") = 0');
assert_igual(150, Money::decimalToCents(' 1.5 '), 'decimalToCents(" 1.5 ") = 150 (trim de espaço)');
assert_lanca(fn() => Money::decimalToCents('abc'), 'decimalToCents("abc") lança exceção');
assert_lanca(fn() => Money::decimalToCents('-5.00'), 'decimalToCents("-5.00") lança exceção (nunca negativo vindo da API)');

// ── formatCents ──────────────────────────────────────────────────────────────
assert_igual('R$ 37,89', Money::formatCents(3789), 'formatCents(3789) = "R$ 37,89"');
assert_igual('R$ 0,00', Money::formatCents(0), 'formatCents(0) = "R$ 0,00"');
assert_igual('R$ 1.234,50', Money::formatCents(123450), 'formatCents(123450) = "R$ 1.234,50" (milhar)');

// ── divideCents ──────────────────────────────────────────────────────────────
assert_igual(null, Money::divideCents(1000, 0), 'divideCents por zero = null');
assert_igual(500, Money::divideCents(1000, 2), 'divideCents(1000, 2) = 500');
assert_igual(333, Money::divideCents(1000, 3), 'divideCents(1000, 3) = 333 (arredondado)');

echo "\n{$total} testes, " . ($total - $falhas) . " OK, {$falhas} falha(s)\n";
exit($falhas > 0 ? 1 : 0);
