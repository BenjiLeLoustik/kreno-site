<?php

declare(strict_types=1);

namespace App\Booking\Enum;

enum BookingStatus: string
{
    case CONFIRMED = 'confirmed';
    case CANCELLED_BY_CUSTOMER = 'cancelled_by_customer';
    case CANCELLED_BY_ESTABLISHMENT = 'cancelled_by_establishment';
    case HONORED = 'honored';
    case NO_SHOW = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::CONFIRMED => 'Confirmée',
            self::CANCELLED_BY_CUSTOMER => 'Annulée par le client',
            self::CANCELLED_BY_ESTABLISHMENT => 'Annulée par l\'établissement',
            self::HONORED => 'Honorée',
            self::NO_SHOW => 'Client absent',
        };
    }
}