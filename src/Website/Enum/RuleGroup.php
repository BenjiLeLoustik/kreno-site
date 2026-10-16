<?php

namespace App\Website\Enum;

enum RuleGroup: string
{
    case BOOKING = 'booking';
    case ABSENCE = 'absence';
    case DISCOUNT = 'discount';
    case SLOT = 'slot';

    public function label(): string
    {
        return match ($this) {
            self::BOOKING => 'Réservations',
            self::ABSENCE => 'Absences',
            self::DISCOUNT => 'Remises',
            self::SLOT => 'Créneaux',
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