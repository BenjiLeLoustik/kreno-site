<?php

namespace App\Website\Enum;

use App\Shared\Helper\Translation;

enum RuleGroup: string
{
    case BOOKING = 'booking';
    case ABSENCE = 'absence';
    case DISCOUNT = 'discount';
    case SLOT = 'slot';

    public function label(): string
    {
        return match ($this) {
            self::BOOKING => Translation::translate('Réservations'),
            self::ABSENCE => Translation::translate('Absences'),
            self::DISCOUNT => Translation::translate('Remises'),
            self::SLOT => Translation::translate('Créneaux'),
        };
    }

    public function fields(): array
    {
        return match ($this) {
            self::BOOKING => [
                'freeCancellationMinutes',
                'bookingLeadMinutes',
                'maxUpcomingBookingsPerUser'
            ],
            self::ABSENCE => [
                'toleratedNoShows',
                'noShowWindowDays',
                'suspensionDays'
            ],
            self::DISCOUNT => [
                'minDiscount',
                'maxDiscount',
                'defaultDiscount'
            ],
            self::SLOT => [
                'publicationWindowDays',
                'autoRemovalMinutes'
            ],
        };
    }
}