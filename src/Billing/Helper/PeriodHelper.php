<?php

namespace App\Billing\Helper;

use App\Billing\Enum\BillingPeriod;
use DateTimeImmutable;

class PeriodHelper
{
    public static function end(DateTimeImmutable $start, BillingPeriod $period, int $count = 1): DateTimeImmutable
    {
        return $start->modify(sprintf('+%d %s', $count, $period === BillingPeriod::YEARLY ? 'year' : 'month'));
    }

    public static function remainingRatio(DateTimeImmutable $start, DateTimeImmutable $end, DateTimeImmutable $now): float
    {
        $total = max(1, $end->getTimestamp() - $start->getTimestamp());
        $left = max(0, $end->getTimestamp() - max($now->getTimestamp(), $start->getTimestamp()));

        return min(1.0, $left / $total);
    }

    public static function monthBounds(DateTimeImmutable $date): array
    {
        $start = $date->modify('first day of this month')->setTime(0, 0);

        return [$start, $start->modify('+1 month')];
    }

    public static function previousMonthBounds(DateTimeImmutable $date): array
    {
        return self::monthBounds($date->modify('first day of previous month'));
    }

    public static function label(DateTimeImmutable $start, DateTimeImmutable $end): string
    {
        $last = $end->modify('-1 day');

        return sprintf('%d %s au %d %s %d', (int) $start->format('j'), self::month($start), (int) $last->format('j'), self::month($last), (int) $last->format('Y'));
    }

    public static function monthName(DateTimeImmutable $date): string
    {
        return ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'][(int) $date->format('n') - 1];
    }

    public static function day(?DateTimeImmutable $date): ?string
    {
        return $date !== null ? sprintf('%d %s %d', (int) $date->format('j'), self::month($date), (int) $date->format('Y')) : null;
    }

    public static function shortDay(?DateTimeImmutable $date): ?string
    {
        return $date !== null ? sprintf('%d %s', (int) $date->format('j'), self::month($date)) : null;
    }

    private static function month(DateTimeImmutable $date): string
    {
        return ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'][(int) $date->format('n') - 1];
    }
}