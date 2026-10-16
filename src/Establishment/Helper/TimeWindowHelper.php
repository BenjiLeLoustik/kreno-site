<?php

namespace App\Establishment\Helper;

use App\Establishment\Enum\TimeWindow;
use DateTimeImmutable;

class TimeWindowHelper
{
    public static function range(string $window, DateTimeImmutable $now): array
    {
        return self::window($window)->range($now);
    }

    public static function choices(string $selected): array
    {
        $choices = [];

        foreach (TimeWindow::cases() as $window) {
            $choices[] = [
                'key' => $window->value,
                'label' => $window->label(),
                'selected' => $window->value === $selected,
            ];
        }

        return $choices;
    }

    private static function window(string $window): TimeWindow
    {
        return TimeWindow::tryFrom($window) ?? TimeWindow::NOW;
    }
}