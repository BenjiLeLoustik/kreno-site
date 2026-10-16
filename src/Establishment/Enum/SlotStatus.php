<?php

declare(strict_types=1);

namespace App\Establishment\Enum;

enum SlotStatus: string
{
    case AVAILABLE = 'available';
    case BOOKED = 'booked';
    case WITHDRAWN = 'withdrawn';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Disponible',
            self::BOOKED => 'Réservé',
            self::WITHDRAWN => 'Retiré',
            self::EXPIRED => 'Expiré',
        };
    }
}