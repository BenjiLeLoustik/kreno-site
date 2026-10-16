<?php

namespace App\Website\Enum;

enum RuleField: string
{
    case FREE_CANCELLATION_MINUTES = 'freeCancellationMinutes';
    case BOOKING_LEAD_MINUTES = 'bookingLeadMinutes';
    case MAX_UPCOMING_BOOKINGS_PER_USER = 'maxUpcomingBookingsPerUser';
    case TOLERATED_NO_SHOWS = 'toleratedNoShows';
    case NO_SHOW_WINDOW_DAYS = 'noShowWindowDays';
    case SUSPENSION_DAYS = 'suspensionDays';
    case MIN_DISCOUNT = 'minDiscount';
    case MAX_DISCOUNT = 'maxDiscount';
    case DEFAULT_DISCOUNT = 'defaultDiscount';
    case PUBLICATION_WINDOW_DAYS = 'publicationWindowDays';
    case AUTO_REMOVAL_MINUTES = 'autoRemovalMinutes';

    public function label(): string
    {
        return match ($this) {
            self::FREE_CANCELLATION_MINUTES => 'Annulation gratuite',
            self::BOOKING_LEAD_MINUTES => 'Délai minimal de réservation',
            self::MAX_UPCOMING_BOOKINGS_PER_USER => 'Réservations en cours par client',
            self::TOLERATED_NO_SHOWS => 'Absences tolérées',
            self::NO_SHOW_WINDOW_DAYS => 'Période comptée',
            self::SUSPENSION_DAYS => 'Durée de suspension',
            self::MIN_DISCOUNT => 'Remise minimale',
            self::MAX_DISCOUNT => 'Remise maximale',
            self::DEFAULT_DISCOUNT => 'Remise par défaut',
            self::PUBLICATION_WINDOW_DAYS => 'Publication à l\'avance',
            self::AUTO_REMOVAL_MINUTES => 'Retrait automatique',
        };
    }

    public function unit(): string
    {
        return match ($this) {
            self::FREE_CANCELLATION_MINUTES, self::BOOKING_LEAD_MINUTES, self::AUTO_REMOVAL_MINUTES => 'min avant',
            self::MAX_UPCOMING_BOOKINGS_PER_USER => 'max',
            self::TOLERATED_NO_SHOWS => 'absences',
            self::NO_SHOW_WINDOW_DAYS, self::SUSPENSION_DAYS, self::PUBLICATION_WINDOW_DAYS => 'jours',
            self::MIN_DISCOUNT, self::MAX_DISCOUNT, self::DEFAULT_DISCOUNT => '%',
        };
    }

    public function getter(): string
    {
        return 'get' . ucfirst($this->value);
    }

    public function setter(): string
    {
        return 'set' . ucfirst($this->value);
    }
}