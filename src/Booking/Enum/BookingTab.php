<?php

namespace App\Booking\Enum;

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
            self::UPCOMING => 'À venir',
            self::PAST => 'Passées',
            self::CANCELLED => 'Annulées',
        };
    }
}