<?php

namespace App\Services\Marketing;

/**
 * Toda data de negócio do módulo Marketing é um dia de calendário ISO ("AAAA-MM-DD") no
 * fuso America/Sao_Paulo. Porta fiel de ads-platform/src/lib/dates.ts.
 */
class Dates
{
    public const TIME_ZONE = 'America/Sao_Paulo';

    private const ISO_DATE = '/^\d{4}-\d{2}-\d{2}$/';

    public static function isIsoDate(string $value): bool
    {
        return (bool) preg_match(self::ISO_DATE, $value);
    }

    public static function todayInSaoPaulo(?\DateTimeImmutable $now = null): string
    {
        $now ??= new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        return $now->setTimezone(new \DateTimeZone(self::TIME_ZONE))->format('Y-m-d');
    }

    /** Aritmética de calendário em UTC, pra virada de horário de verão nunca deslocar o dia. */
    public static function addDays(string $date, int $days): string
    {
        [$year, $month, $day] = array_map('intval', explode('-', $date));
        $dt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $dt = $dt->setDate($year, $month, $day)->setTime(0, 0)->modify("{$days} days");
        return $dt->format('Y-m-d');
    }

    /** @return string[] */
    public static function eachDay(string $from, string $to): array
    {
        $days = [];
        for ($current = $from; $current <= $to; $current = self::addDays($current, 1)) {
            $days[] = $current;
        }
        return $days;
    }

    public static function formatDayMonth(string $date): string
    {
        [, $month, $day] = explode('-', $date);
        return "{$day}/{$month}";
    }
}
