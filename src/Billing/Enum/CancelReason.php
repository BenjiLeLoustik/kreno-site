<?php

namespace App\Billing\Enum;

enum CancelReason: string
{
    case PRICE = 'price';
    case VOLUME = 'volume';
    case PAUSE = 'pause';
    case CLOSE = 'close';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PRICE => 'C’est trop cher pour moi',
            self::VOLUME => 'Je n’ai pas assez de réservations',
            self::PAUSE => 'Je ferme temporairement',
            self::CLOSE => 'Je ferme définitivement',
            self::OTHER => 'Autre raison',
        };
    }
}