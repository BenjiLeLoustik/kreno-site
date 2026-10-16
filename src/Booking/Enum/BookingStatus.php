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

    public function proClosedText(string $client): ?string
    {
        return match ($this) {
            self::CONFIRMED => null,
            self::HONORED => 'Rendez-vous marqué comme honoré.',
            self::NO_SHOW => 'Absence enregistrée et signalée à Kréno.',
            self::CANCELLED_BY_CUSTOMER => 'Le client a annulé ce rendez-vous.',
            self::CANCELLED_BY_ESTABLISHMENT => sprintf(
                'Rendez-vous annulé. %s est prévenu(e) par e-mail et d’autres créneaux lui sont proposés.',
                $client,
            ),
        };
    }
}