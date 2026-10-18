<?php

namespace App\Shared\Helper;

use DateTimeImmutable;

class DurationHelper
{
    public static function format(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes . ' min';
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return $rest === 0 ? $hours . ' h' : sprintf('%d h %02d', $hours, $rest);
    }

    public static function remaining(DateTimeImmutable $until, ?DateTimeImmutable $now = null): string
    {
        $now ??= new DateTimeImmutable();
        $minutes = intdiv(max(0, $until->getTimestamp() - $now->getTimestamp()), 60);

        if ($minutes < 1) {
            return Translation::translate('Encore moins de 1min');
        }

        if ($minutes < 60) {
            return sprintf(Translation::translate('Encore %dmin'), $minutes);
        }

        $rest = $minutes % 60;

        return $rest === 0
            ? sprintf(Translation::translate('Encore %d h'), intdiv($minutes, 60))
            : sprintf(Translation::translate('Encore %d h %dmin'), intdiv($minutes, 60), $rest);
    }
}