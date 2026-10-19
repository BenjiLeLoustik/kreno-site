<?php

namespace App\Establishment\Enum;

use App\Shared\Helper\Translation;

enum StatsPeriod: int
{
    case WEEK = 7;
    case MONTH = 30;
    case QUARTER = 90;

    public function label(): string
    {
        return match ($this) {
            self::WEEK => Translation::translate('7 jours'),
            self::MONTH => Translation::translate('30 jours'),
            self::QUARTER => Translation::translate('90 jours'),
        };
    }
}