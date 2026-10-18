<?php

namespace App\Booking\Enum;

use App\Shared\Helper\Translation;

enum BookingDisplayStatus: string
{
    case COMING = 'coming';
    case DONE = 'done';
    case CANCELLED = 'cancelled';
    case CANCELLED_BY_ESTABLISHMENT = 'cancelled_by_establishment';
    case NO_SHOW = 'noshow';
    case PENDING = 'pending';

    public function customerLabel(): string
    {
        return match ($this) {
            self::COMING => Translation::translate('Confirmée'),
            self::DONE => Translation::translate('Honorée'),
            self::CANCELLED => Translation::translate('Annulée'),
            self::CANCELLED_BY_ESTABLISHMENT => Translation::translate('Annulée par l\'établissement'),
            self::NO_SHOW => Translation::translate('Absence'),
            self::PENDING => Translation::translate('En attente de clôture'),
        };
    }

    public function proLabel(): string
    {
        return match ($this) {
            self::COMING => Translation::translate('À venir'),
            self::DONE => Translation::translate('Honorée'),
            self::CANCELLED => Translation::translate('Annulée par le client'),
            self::CANCELLED_BY_ESTABLISHMENT => Translation::translate('Annulée par vous'),
            self::NO_SHOW => Translation::translate('Absent'),
            self::PENDING => Translation::translate('À clôturer'),
        };
    }

    public function adminLabel(): string
    {
        return match ($this) {
            self::COMING => Translation::translate('À venir'),
            self::DONE => Translation::translate('Honorée'),
            self::CANCELLED,
            self::CANCELLED_BY_ESTABLISHMENT => Translation::translate('Annulée'),
            self::NO_SHOW => Translation::translate('Absent'),
            self::PENDING => Translation::translate('À clôturer'),
        };
    }
}