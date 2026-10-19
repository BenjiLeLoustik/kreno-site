<?php

namespace App\Website\Enum;

use App\Shared\Helper\Translation;

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
            self::FREE_CANCELLATION_MINUTES => Translation::translate('Annulation gratuite'),
            self::BOOKING_LEAD_MINUTES => Translation::translate('Délai minimal de réservation'),
            self::MAX_UPCOMING_BOOKINGS_PER_USER => Translation::translate('Réservations en cours par client'),
            self::TOLERATED_NO_SHOWS => Translation::translate('Absences tolérées'),
            self::NO_SHOW_WINDOW_DAYS => Translation::translate('Période comptée'),
            self::SUSPENSION_DAYS => Translation::translate('Durée de suspension'),
            self::MIN_DISCOUNT => Translation::translate('Remise minimale'),
            self::MAX_DISCOUNT => Translation::translate('Remise maximale'),
            self::DEFAULT_DISCOUNT => Translation::translate('Remise par défaut'),
            self::PUBLICATION_WINDOW_DAYS => Translation::translate('Publication à l\'avance'),
            self::AUTO_REMOVAL_MINUTES => Translation::translate('Retrait automatique'),
        };
    }

    public function unit(): string
    {
        return match ($this) {
            self::FREE_CANCELLATION_MINUTES,
            self::BOOKING_LEAD_MINUTES,
            self::AUTO_REMOVAL_MINUTES => Translation::translate('min avant'),
            self::MAX_UPCOMING_BOOKINGS_PER_USER => Translation::translate('max'),
            self::TOLERATED_NO_SHOWS => Translation::translate('absences'),
            self::NO_SHOW_WINDOW_DAYS,
            self::SUSPENSION_DAYS,
            self::PUBLICATION_WINDOW_DAYS => Translation::translate('jours'),
            self::MIN_DISCOUNT,
            self::MAX_DISCOUNT,
            self::DEFAULT_DISCOUNT => '%',
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