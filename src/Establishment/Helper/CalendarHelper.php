<?php

namespace App\Establishment\Helper;

use App\Shared\Enum\Month;
use App\Shared\Enum\Weekday;
use DateTimeImmutable;

class CalendarHelper
{
    public const MORNING_END_HOUR = 12;

    public static function dayLabel(DateTimeImmutable $date, ?DateTimeImmutable $now = null): string
    {
        $today = ($now ?? new DateTimeImmutable())->setTime(0, 0);
        $days = (int) $today->diff($date->setTime(0, 0))->format('%r%a');

        return match ($days) {
            0 => 'Aujourd’hui',
            1 => 'Demain',
            -1 => 'Hier',
            default => self::shortDate($date),
        };
    }

    public static function shortDate(DateTimeImmutable $date): string
    {
        return sprintf(
            '%s %d %s',
            Weekday::of($date)->short(),
            (int) $date->format('j'),
            Month::of($date)->short()
        );
    }

    public static function longDate(DateTimeImmutable $date): string
    {
        return sprintf(
            '%s %d %s',
            Weekday::of($date)->label(),
            (int) $date->format('j'),
            Month::of($date)->label()
        );
    }

    public static function monthYear(DateTimeImmutable $date): string
    {
        return sprintf('%s %d', Month::of($date)->short(), (int) $date->format('Y'));
    }

    public static function weekLabel(DateTimeImmutable $monday): string
    {
        $sunday = $monday->modify('+6 days');
        $end = sprintf(
            '%d %s %d',
            (int) $sunday->format('j'),
            Month::of($sunday)->short(),
            (int) $sunday->format('Y')
        );

        if ($monday->format('n') === $sunday->format('n')) {
            return sprintf('%d – %s', (int) $monday->format('j'), $end);
        }

        return sprintf('%d %s – %s', (int) $monday->format('j'), Month::of($monday)->short(), $end);
    }

    public static function mondayOf(DateTimeImmutable $date): DateTimeImmutable
    {
        return $date->setTime(0, 0)
            ->modify(sprintf('-%d days', (int) $date->format('N') - 1));
    }

    public static function parseWeek(?string $week, ?DateTimeImmutable $now = null): DateTimeImmutable
    {
        $now ??= new DateTimeImmutable();

        if ($week !== null && preg_match('/^(\d{4})-W(\d{2})$/', $week, $matches) === 1) {
            return $now->setISODate((int) $matches[1], (int) $matches[2])->setTime(0, 0);
        }

        return self::mondayOf($now);
    }

    public static function weekCode(DateTimeImmutable $date): string
    {
        return $date->format('o-\WW');
    }

    public static function minutesOfDay(DateTimeImmutable $date): int
    {
        return (int) $date->format('G') * 60 + (int) $date->format('i');
    }

    public static function relative(DateTimeImmutable $date, ?DateTimeImmutable $now = null): string
    {
        $now ??= new DateTimeImmutable();
        $minutes = intdiv(max(0, $now->getTimestamp() - $date->getTimestamp()), 60);

        if ($minutes < 1) {
            return 'à l\'instant';
        }

        if ($minutes < 60) {
            return sprintf('il y a %d min', $minutes);
        }

        if ($date->format('Y-m-d') !== $now->format('Y-m-d')) {
            return mb_strtolower(self::dayLabel($date, $now));
        }

        if ((int) $date->format('G') < self::MORNING_END_HOUR) {
            return 'ce matin';
        }

        return sprintf('il y a %d h', intdiv($minutes, 60));
    }
}