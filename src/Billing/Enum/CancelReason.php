<?php

namespace App\Billing\Enum;

use App\Shared\Helper\Translation;

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
            self::PRICE => Translation::translate('C’est trop cher pour moi'),
            self::VOLUME => Translation::translate('Je n’ai pas assez de réservations'),
            self::PAUSE => Translation::translate('Je ferme temporairement'),
            self::CLOSE => Translation::translate('Je ferme définitivement'),
            self::OTHER => Translation::translate('Autre raison'),
        };
    }
}