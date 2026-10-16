<?php

namespace App\Establishment\Enum;

enum SlotInfo: string
{
    case PAYMENT_ON_SITE = 'payment_on_site';
    case FREE_CANCELLATION = 'free_cancellation';
    case SEAT_HELD = 'seat_held';

    public function title(): string
    {
        return match ($this) {
            self::PAYMENT_ON_SITE => 'Paiement sur place',
            self::FREE_CANCELLATION => 'Annulation gratuite',
            self::SEAT_HELD => 'Place bloquée',
        };
    }

    public function text(string $cancellationDelay): string
    {
        return match ($this) {
            self::PAYMENT_ON_SITE => 'Aucune carte demandée en ligne.',
            self::FREE_CANCELLATION => sprintf('Jusqu’à %s avant le rendez-vous.', $cancellationDelay),
            self::SEAT_HELD => 'À ton nom dès la confirmation.',
        };
    }
}