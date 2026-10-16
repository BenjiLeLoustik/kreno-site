<?php

namespace App\Establishment\Helper;

use DateTimeImmutable;

class TimeWindowHelper
{
    public const WINDOWS = [
        'now' => 'Maintenant',
        'morning' => 'Ce matin',
        'afternoon' => 'Cet après-midi',
        'evening' => 'Ce soir',
        'tomorrow' => 'Demain',
        'week' => 'Cette semaine',
    ];

    public static function range(string $window, DateTimeImmutable $now): array
    {
        $today = $now->setTime(0, 0);

        [$from, $to] = match ($window) {
            'morning' => [$today->setTime(6, 0), $today->setTime(12, 0)],
            'afternoon' => [$today->setTime(12, 0), $today->setTime(18, 0)],
            'evening' => [$today->setTime(18, 0), $today->modify('+1 day')],
            'tomorrow' => [$today->modify('+1 day'), $today->modify('+2 days')],
            'week' => [$now, $today->modify('+7 days')],
            default => [$now, null],
        };

        return [max($from, $now), $to];
    }

    public static function choices(string $selected): array
    {
        return array_map(static fn (string $key, string $label): array => ['key' => $key, 'label' => $label, 'selected' => $key === $selected], array_keys(self::WINDOWS), self::WINDOWS);
    }
}