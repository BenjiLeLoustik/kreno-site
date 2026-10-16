<?php

namespace App\Establishment\Enum;

enum StatsPeriod: int
{
    case WEEK = 7;
    case MONTH = 30;
    case QUARTER = 90;

    public function label(): string
    {
        return match ($this) {
            self::WEEK => '7 jours',
            self::MONTH => '30 jours',
            self::QUARTER => '90 jours',
        };
    }
}