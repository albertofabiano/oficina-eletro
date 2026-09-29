<?php

namespace App\Services\Marketing;

/**
 * Dinheiro do módulo Marketing é sempre centavos inteiros (int) — nunca float, nem na
 * conversão do texto que a API da Meta devolve ("12.34"). Porta fiel de
 * ads-platform/src/lib/money.ts.
 */
class Money
{
    public static function formatCents(int $cents): string
    {
        return 'R$ ' . number_format($cents / 100, 2, ',', '.');
    }

    /** Divisão inteira arredondada pra cima a partir de 0,5; null se o divisor for 0. */
    public static function divideCents(int $cents, int $divisor): ?int
    {
        if ($divisor === 0) return null;
        return (int) round($cents / $divisor);
    }

    /**
     * Converte um valor decimal como a API de anúncio manda ("12.34", "7", "0.105") em
     * centavos inteiros, sem passar por float. Decimais extras arredondam pra cima a
     * partir de 0,5 no 3º dígito.
     */
    public static function decimalToCents(string $amount): int
    {
        if (!preg_match('/^(\d+)(?:\.(\d+))?$/', trim($amount), $m)) {
            throw new \InvalidArgumentException("invalid amount: {$amount}");
        }
        $whole = $m[1];
        $fraction = $m[2] ?? '';
        $padded = substr($fraction . '000', 0, 3);
        $cents = ((int) $whole) * 100 + (int) substr($padded, 0, 2);
        return ((int) $padded[2]) >= 5 ? $cents + 1 : $cents;
    }
}
