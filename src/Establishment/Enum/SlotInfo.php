<?php

namespace App\Establishment\Enum;

use App\Shared\Helper\Translation;

enum SlotInfo: string
{
    case PAYMENT_ON_SITE = 'payment_on_site';
    case FREE_CANCELLATION = 'free_cancellation';
    case SEAT_HELD = 'seat_held';

    public function title(): string
    {
        return match ($this) {
            self::PAYMENT_ON_SITE => Translation::translate('Paiement sur place'),
            self::FREE_CANCELLATION => Translation::translate('Annulation gratuite'),
            self::SEAT_HELD => Translation::translate('Place bloquée'),
        };
    }

    public function text(string $cancellationDelay): string
    {
        return match ($this) {
            self::PAYMENT_ON_SITE => Translation::translate('Aucune carte demandée en ligne.'),
            self::FREE_CANCELLATION => sprintf(Translation::translate('Jusqu’à %s avant le rendez-vous.'), $cancellationDelay),
            self::SEAT_HELD => Translation::translate('À ton nom dès la confirmation.'),
        };
    }
}