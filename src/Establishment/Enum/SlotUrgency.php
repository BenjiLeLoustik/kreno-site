<?php

namespace App\Establishment\Enum;

enum SlotUrgency: string
{
    case LAST = 'last';
    case SOON = 'soon';
    case FRESH = 'fresh';

    public const LAST_MINUTES = 10;

    public const SOON_MINUTES = 30;

    public static function fromRemainingMinutes(int $minutes): self
    {
        if ($minutes <= self::LAST_MINUTES) {
            return self::LAST;
        }

        if ($minutes <= self::SOON_MINUTES) {
            return self::SOON;
        }

        return self::FRESH;
    }
}