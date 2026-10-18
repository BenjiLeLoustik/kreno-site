<?php

namespace App\Booking\Enum;

use App\Shared\Helper\Translation;

enum BookingTab: string
{
    case UPCOMING = 'upcoming';
    case PAST = 'past';
    case CANCELLED = 'cancelled';

    public static function of(string $tab): self
    {
        return self::tryFrom($tab) ?? self::UPCOMING;
    }

    public function label(): string
    {
        return match ($this) {
            self::UPCOMING => Translation::translate('À venir'),
            self::PAST => Translation::translate('Passées'),
            self::CANCELLED => Translation::translate('Annulées'),
        };
    }
}