<?php

namespace App\Website\Enum;

enum CancellationDelay
{
    case HOURS;
    case MINUTES;

    public static function format(int $minutes): string
    {
        if ($minutes >= 60 && $minutes % 60 === 0) {
            return self::HOURS->text(intdiv($minutes, 60));
        }

        return self::MINUTES->text($minutes);
    }

    public function text(int $value): string
    {
        return sprintf($this->pattern(), $value);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::HOURS => '%d h',
            self::MINUTES => '%d min',
        };
    }
}