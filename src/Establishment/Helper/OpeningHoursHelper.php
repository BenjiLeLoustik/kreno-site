<?php

namespace App\Establishment\Helper;

use App\Entity\Establishment\EstablishmentOpeningHour;
use App\Establishment\Enum\OpeningState;
use App\Shared\Enum\Weekday;
use DateTimeImmutable;

class OpeningHoursHelper
{
    public const DEFAULT_START_HOUR = 8;

    public const DEFAULT_END_HOUR = 20;

    public static function byWeekday(array $hours): array
    {
        $week = [];

        foreach (Weekday::cases() as $weekday) {
            $week[$weekday->value] = [];
        }

        foreach ($hours as $hour) {
            $range = self::range($hour);

            if ($range !== null) {
                $week[$hour->getWeekday()->value][] = $range;
            }
        }

        foreach ($week as &$ranges) {
            usort($ranges, static fn (array $a, array $b): int => strcmp($a[0], $b[0]));
        }

        unset($ranges);

        return $week;
    }

    public static function rangesLabel(array $ranges): string
    {
        if ($ranges === []) {
            return OpeningState::CLOSED->label();
        }

        $labels = [];

        foreach ($ranges as [$opensAt, $closesAt]) {
            $labels[] = self::time($opensAt) . ' – ' . self::time($closesAt);
        }

        return implode(' / ', $labels);
    }

    public static function group(array $hours): array
    {
        $groups = self::mergeWrappingGroup(self::groupBySchedule($hours));
        $result = [];

        foreach ($groups as $group) {
            $result[] = [
                'day' => self::daysLabel($group['days']),
                'time' => $group['time'],
                'closed' => $group['time'] === OpeningState::CLOSED->label(),
            ];
        }

        return $result;
    }

    public static function daysLabel(array $weekdays): string
    {
        $names = [];

        foreach ($weekdays as $weekday) {
            $names[] = Weekday::from($weekday)->label();
        }

        $first = $names[0];
        $last = mb_strtolower($names[array_key_last($names)]);

        if (count($names) === 1) {
            return $first;
        }

        if (count($names) === 2) {
            return sprintf('%s et %s', $first, $last);
        }

        return sprintf('%s au %s', $first, $last);
    }

    public static function isOpenAt(array $hours, DateTimeImmutable $at): bool
    {
        $time = $at->format('H:i');

        foreach (self::byWeekday($hours)[Weekday::of($at)->value] as [$opensAt, $closesAt]) {
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

        foreach (self::byWeekday($hours)[Weekday::of($now)->value] as [$opensAt, $closesAt]) {
            if ($time >= $opensAt && $time < $closesAt) {
                return OpeningState::OPEN_UNTIL->label(self::time($closesAt));
            }

            if ($time < $opensAt) {
                return OpeningState::OPENS_AT->label(self::time($opensAt));
            }
        }

        return OpeningState::CLOSED->label();
    }

    public static function bounds(
        array $hours,
        int $defaultStart = self::DEFAULT_START_HOUR,
        int $defaultEnd = self::DEFAULT_END_HOUR,
    ): array {
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

    private static function range(mixed $hour): ?array
    {
        if (!$hour instanceof EstablishmentOpeningHour) {
            return null;
        }

        $opensAt = $hour->getOpensAt();
        $closesAt = $hour->getClosesAt();

        if ($opensAt === null || $closesAt === null) {
            return null;
        }

        return [$opensAt->format('H:i'), $closesAt->format('H:i')];
    }

    private static function groupBySchedule(array $hours): array
    {
        $groups = [];

        foreach (self::byWeekday($hours) as $weekday => $ranges) {
            $label = self::rangesLabel($ranges);
            $last = array_key_last($groups);

            if ($last !== null && $groups[$last]['time'] === $label) {
                $groups[$last]['days'][] = $weekday;
                continue;
            }

            $groups[] = [
                'days' => [
                    $weekday
                ],
                'time' => $label
            ];
        }

        return $groups;
    }

    private static function mergeWrappingGroup(array $groups): array
    {
        $last = array_key_last($groups);

        if (count($groups) <= 1 || $groups[0]['time'] !== $groups[$last]['time']) {
            return $groups;
        }

        $first = array_shift($groups);
        $last = array_key_last($groups);
        $groups[$last]['days'] = [
            ...$groups[$last]['days'],
            ...$first['days']
        ];

        return $groups;
    }
}