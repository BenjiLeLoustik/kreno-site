<?php

declare(strict_types=1);

namespace App\Booking\Enum;

use App\Shared\Helper\Translation;

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
            self::CONFIRMED => Translation::translate('Confirmée'),
            self::CANCELLED_BY_CUSTOMER => Translation::translate('Annulée par le client'),
            self::CANCELLED_BY_ESTABLISHMENT => Translation::translate('Annulée par l\'établissement'),
            self::HONORED => Translation::translate('Honorée'),
            self::NO_SHOW => Translation::translate('Client absent'),
        };
    }

    public function proClosedText(string $client): ?string
    {
        return match ($this) {
            self::CONFIRMED => null,
            self::HONORED => Translation::translate('Rendez-vous marqué comme honoré.'),
            self::NO_SHOW => Translation::translate('Absence enregistrée et signalée à Kréno.'),
            self::CANCELLED_BY_CUSTOMER => Translation::translate('Le client a annulé ce rendez-vous.'),
            self::CANCELLED_BY_ESTABLISHMENT => sprintf(
                Translation::translate('Rendez-vous annulé. %s est prévenu(e) par e-mail et d’autres créneaux lui sont proposés.'),
                $client,
            ),
        };
    }
}