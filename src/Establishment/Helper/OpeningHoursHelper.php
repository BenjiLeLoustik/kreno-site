<?php

namespace App\Establishment\Helper;

use App\Entity\Establishment\EstablishmentOpeningHour;
use App\Enum\Calendar\Weekday;
use DateTimeImmutable;

class OpeningHoursHelper
{
    public const CLOSED = 'Fermé';

    public static function byWeekday(array $hours): array
    {
        $week = [];

        foreach (Weekday::cases() as $weekday) {
            $week[$weekday->value] = [];
        }

        foreach ($hours as $hour) {
            if ($hour instanceof EstablishmentOpeningHour && $hour->getOpensAt() !== null && $hour->getClosesAt() !== null) {
                $week[$hour->getWeekday()->value][] = [$hour->getOpensAt()->format('H:i'), $hour->getClosesAt()->format('H:i')];
            }
        }

        return array_map(static function (array $ranges): array {
            usort($ranges, static fn (array $a, array $b): int => strcmp($a[0], $b[0]));

            return $ranges;
        }, $week);
    }

    public static function rangesLabel(array $ranges): string
    {
        if ($ranges === []) {
            return self::CLOSED;
        }

        return implode(' / ', array_map(static fn (array $range): string => self::time($range[0]) . ' – ' . self::time($range[1]), $ranges));
    }

    public static function group(array $hours): array
    {
        $groups = [];

        foreach (self::byWeekday($hours) as $weekday => $ranges) {
            $label = self::rangesLabel($ranges);
            $last = array_key_last($groups);

            if ($last !== null && $groups[$last]['time'] === $label) {
                $groups[$last]['days'][] = $weekday;
                continue;
            }

            $groups[] = ['days' => [$weekday], 'time' => $label];
        }

        if (count($groups) > 1 && $groups[0]['time'] === $groups[array_key_last($groups)]['time']) {
            $first = array_shift($groups);
            $groups[array_key_last($groups)]['days'] = array_merge($groups[array_key_last($groups)]['days'], $first['days']);
        }

        return array_map(static fn (array $group): array => ['day' => self::daysLabel($group['days']), 'time' => $group['time'], 'closed' => $group['time'] === self::CLOSED], $groups);
    }

    public static function daysLabel(array $weekdays): string
    {
        $names = array_map(static fn (int $weekday): string => Weekday::from($weekday)->label(), $weekdays);

        return match (count($names)) {
            1 => $names[0],
            2 => $names[0] . ' et ' . mb_strtolower($names[1]),
            default => $names[0] . ' au ' . mb_strtolower($names[array_key_last($names)]),
        };
    }

    public static function isOpenAt(array $hours, DateTimeImmutable $at): bool
    {
        $time = $at->format('H:i');

        foreach (self::byWeekday($hours)[(int) $at->format('N')] as [$opensAt, $closesAt]) {
            if ($time >= $opensAt && $time < $closesAt) {
                return true;
            }
        }

        return false;
    }

    public static function status(array $hours, ?DateTimeImmutable $now = null): string
    {
        $now ??= new DateTimeImmutable();
        $time = $now->format('H:i');

        foreach (self::byWeekday($hours)[(int) $now->format('N')] as [$opensAt, $closesAt]) {
            if ($time >= $opensAt && $time < $closesAt) {
                return 'Ouvert jusqu\'à ' . self::time($closesAt);
            }

            if ($time < $opensAt) {
                return 'Ouvre à ' . self::time($opensAt);
            }
        }

        return 'Fermé';
    }

    public static function bounds(array $hours, int $defaultStart = 8, int $defaultEnd = 20): array
    {
        $start = null;
        $end = null;

        foreach (self::byWeekday($hours) as $ranges) {
            foreach ($ranges as [$opensAt, $closesAt]) {
                $start = min($start ?? 24, intdiv(self::minutes($opensAt), 60));
                $end = max($end ?? 0, (int) ceil(self::minutes($closesAt) / 60));
            }
        }

        return [$start ?? $defaultStart, $end ?? $defaultEnd];
    }

    public static function time(string $time): string
    {
        return (int) substr($time, 0, 2) . substr($time, 2, 3);
    }

    public static function minutes(string $time): int
    {
        return (int) substr($time, 0, 2) * 60 + (int) substr($time, 3, 2);
    }
}