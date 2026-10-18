<?php

namespace App\Booking\Enum;

use App\Shared\Helper\Translation;

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
            self::SUMMARY => Translation::translate('%s chez %s'),
            self::DESCRIPTION => Translation::translate('Réservation %s · paiement sur place'),
        };
    }
}