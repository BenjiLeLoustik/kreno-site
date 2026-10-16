<?php

namespace App\Booking\Enum;

enum BookingCalendarText
{
    case SUMMARY;
    case DESCRIPTION;

    public function text(string|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::SUMMARY => '%s chez %s',
            self::DESCRIPTION => 'Réservation %s · paiement sur place',
        };
    }
}