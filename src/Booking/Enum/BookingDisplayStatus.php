<?php

namespace App\Booking\Enum;

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
            self::COMING => 'Confirmée',
            self::DONE => 'Honorée',
            self::CANCELLED => 'Annulée',
            self::CANCELLED_BY_ESTABLISHMENT => 'Annulée par l\'établissement',
            self::NO_SHOW => 'Absence',
            self::PENDING => 'En attente de clôture',
        };
    }

    public function proLabel(): string
    {
        return match ($this) {
            self::COMING => 'À venir',
            self::DONE => 'Honorée',
            self::CANCELLED => 'Annulée par le client',
            self::CANCELLED_BY_ESTABLISHMENT => 'Annulée par vous',
            self::NO_SHOW => 'Absent',
            self::PENDING => 'À clôturer',
        };
    }

    public function adminLabel(): string
    {
        return match ($this) {
            self::COMING => 'À venir',
            self::DONE => 'Honorée',
            self::CANCELLED, self::CANCELLED_BY_ESTABLISHMENT => 'Annulée',
            self::NO_SHOW => 'Absent',
            self::PENDING => 'À clôturer',
        };
    }
}