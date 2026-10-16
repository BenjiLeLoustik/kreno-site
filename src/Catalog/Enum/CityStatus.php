<?php

declare(strict_types=1);

namespace App\Catalog\Enum;

enum CityStatus: string
{
    case OPEN = 'open';
    case WAITLIST = 'waitlist';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Ouverte',
            self::WAITLIST => 'Liste d\'attente',
            self::CLOSED => 'Fermée',
        };
    }
}