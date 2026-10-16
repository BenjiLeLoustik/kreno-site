<?php

namespace App\Establishment\Helper;

use DateTimeImmutable;

class CalendarHelper
{
    public const WEEKDAYS = [1 => 'lundi', 2 => 'mardi', 3 => 'mercredi', 4 => 'jeudi', 5 => 'vendredi', 6 => 'samedi', 7 => 'dimanche'];

    public const SHORT_WEEKDAYS = [1 => 'Lun.', 2 => 'Mar.', 3 => 'Mer.', 4 => 'Jeu.', 5 => 'Ven.', 6 => 'Sam.', 7 => 'Dim.'];

    public const MONTHS = [1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril', 5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août', 9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'];

    public const SHORT_MONTHS = [1 => 'janv.', 2 => 'févr.', 3 => 'mars', 4 => 'avr.', 5 => 'mai', 6 => 'juin', 7 => 'juil.', 8 => 'août', 9 => 'sept.', 10 => 'oct.', 11 => 'nov.', 12 => 'déc.'];

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
        return sprintf('%s %d %s', self::SHORT_WEEKDAYS[(int) $date->format('N')], (int) $date->format('j'), self::SHORT_MONTHS[(int) $date->format('n')]);
    }

    public static function longDate(DateTimeImmutable $date): string
    {
        return sprintf('%s %d %s', ucfirst(self::WEEKDAYS[(int) $date->format('N')]), (int) $date->format('j'), self::MONTHS[(int) $date->format('n')]);
    }

    public static function monthYear(DateTimeImmutable $date): string
    {
        return sprintf('%s %d', self::SHORT_MONTHS[(int) $date->format('n')], (int) $date->format('Y'));
    }

    public static function weekLabel(DateTimeImmutable $monday): string
    {
        $sunday = $monday->modify('+6 days');

        if ($monday->format('n') === $sunday->format('n')) {
            return sprintf('%d – %d %s %d', (int) $monday->format('j'), (int) $sunday->format('j'), self::SHORT_MONTHS[(int) $sunday->format('n')], (int) $sunday->format('Y'));
        }

        return sprintf('%d %s – %d %s %d', (int) $monday->format('j'), self::SHORT_MONTHS[(int) $monday->format('n')], (int) $sunday->format('j'), self::SHORT_MONTHS[(int) $sunday->format('n')], (int) $sunday->format('Y'));
    }

    public static function mondayOf(DateTimeImmutable $date): DateTimeImmutable
    {
        return $date->setTime(0, 0)->modify(sprintf('-%d days', (int) $date->format('N') - 1));
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

        if ($date->format('Y-m-d') === $now->format('Y-m-d')) {
            return (int) $date->format('G') < 12 ? 'ce matin' : sprintf('il y a %d h', intdiv($minutes, 60));
        }

        return mb_strtolower(self::dayLabel($date, $now));
    }
}