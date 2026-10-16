<?php

namespace App\Website\Enum;

enum InstallRuleField: string
{
    case FREE_CANCELLATION_MINUTES = 'freeCancellationMinutes';
    case BOOKING_LEAD_MINUTES = 'bookingLeadMinutes';
    case AUTO_REMOVAL_MINUTES = 'autoRemovalMinutes';
    case MAX_UPCOMING_BOOKINGS_PER_USER = 'maxUpcomingBookingsPerUser';
    case TOLERATED_NO_SHOWS = 'toleratedNoShows';
    case NO_SHOW_WINDOW_DAYS = 'noShowWindowDays';
    case SUSPENSION_DAYS = 'suspensionDays';
    case MIN_DISCOUNT = 'minDiscount';
    case MAX_DISCOUNT = 'maxDiscount';
    case DEFAULT_DISCOUNT = 'defaultDiscount';
    case PUBLICATION_WINDOW_DAYS = 'publicationWindowDays';

    public function label(): string
    {
        return match ($this) {
            self::FREE_CANCELLATION_MINUTES => 'Annulation gratuite jusqu\'à',
            self::BOOKING_LEAD_MINUTES => 'Réservation possible jusqu\'à',
            self::AUTO_REMOVAL_MINUTES => 'Retrait automatique du créneau',
            self::MAX_UPCOMING_BOOKINGS_PER_USER => 'Réservations à venir par client',
            self::TOLERATED_NO_SHOWS => 'Absences tolérées',
            self::NO_SHOW_WINDOW_DAYS => 'Sur une période de',
            self::SUSPENSION_DAYS => 'Durée de suspension',
            self::MIN_DISCOUNT => 'Remise minimale',
            self::MAX_DISCOUNT => 'Remise maximale',
            self::DEFAULT_DISCOUNT => 'Remise proposée par défaut',
            self::PUBLICATION_WINDOW_DAYS => 'Publication jusqu\'à',
        };
    }

    public function unit(): string
    {
        return match ($this) {
            self::FREE_CANCELLATION_MINUTES, self::BOOKING_LEAD_MINUTES, self::AUTO_REMOVAL_MINUTES => 'min avant',
            self::MAX_UPCOMING_BOOKINGS_PER_USER => 'maximum',
            self::TOLERATED_NO_SHOWS => 'absences',
            self::NO_SHOW_WINDOW_DAYS, self::SUSPENSION_DAYS => 'jours',
            self::MIN_DISCOUNT, self::MAX_DISCOUNT, self::DEFAULT_DISCOUNT => '%',
            self::PUBLICATION_WINDOW_DAYS => 'jours à l\'avance',
        };
    }

    public function defaultValue(): int
    {
        return match ($this) {
            self::FREE_CANCELLATION_MINUTES => 60,
            self::BOOKING_LEAD_MINUTES => 15,
            self::AUTO_REMOVAL_MINUTES => 10,
            self::MAX_UPCOMING_BOOKINGS_PER_USER => 2,
            self::TOLERATED_NO_SHOWS => 2,
            self::NO_SHOW_WINDOW_DAYS => 90,
            self::SUSPENSION_DAYS => 30,
            self::MIN_DISCOUNT => 10,
            self::MAX_DISCOUNT => 50,
            self::DEFAULT_DISCOUNT => 30,
            self::PUBLICATION_WINDOW_DAYS => 7,
        };
    }
}