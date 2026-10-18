<?php

declare(strict_types=1);

namespace App\Establishment\Enum;

use App\Shared\Helper\Translation;

enum SlotStatus: string
{
    case AVAILABLE = 'available';
    case BOOKED = 'booked';
    case WITHDRAWN = 'withdrawn';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => Translation::translate('Disponible'),
            self::BOOKED => Translation::translate('Réservé'),
            self::WITHDRAWN => Translation::translate('Retiré'),
            self::EXPIRED => Translation::translate('Expiré'),
        };
    }
}