<?php

namespace App\Billing\Helper;

use App\Billing\Enum\BillingPeriod;
use App\Shared\Enum\Month;
use App\Shared\Helper\Translation;
use DateTimeImmutable;

class PeriodHelper
{
    public static function end(DateTimeImmutable $start, BillingPeriod $period, int $count = 1): DateTimeImmutable
    {
        $period = 'month';
        if ($period === BillingPeriod::YEARLY) {
            $period = 'year';
        }

        return $start->modify(
            sprintf('+%d %s', $count, $period)
        );
    }

    public static function remainingRatio(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        DateTimeImmutable $now
    ): float {
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

        return sprintf(
            Translation::translate('%d %s au %d %s %d'),
            (int) $start->format('j'),
            self::month($start),
            (int) $last->format('j'),
            self::month($last),
            (int) $last->format('Y')
        );
    }

    public static function monthName(DateTimeImmutable $date): string
    {
        return Month::of($date)->label();
    }

    public static function day(?DateTimeImmutable $date): ?string
    {
        if ($date !== null) {
            return sprintf(
                '%d %s %d',
                (int) $date->format('j'),
                self::month($date),
                (int) $date->format('Y')
            );
        }

        return null;
    }

    public static function shortDay(?DateTimeImmutable $date): ?string
    {
        if ($date !== null) {
            return sprintf(
                '%d %s',
                (int) $date->format('j'),
                self::month($date)
            );
        }

        return null;
    }

    private static function month(DateTimeImmutable $date): string
    {
        return Month::of($date)->short();
    }
}