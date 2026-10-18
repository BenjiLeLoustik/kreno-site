<?php

declare(strict_types=1);

namespace App\Catalog\Enum;

use App\Shared\Helper\Translation;

enum CityStatus: string
{
    case OPEN = 'open';
    case WAITLIST = 'waitlist';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => Translation::translate('Ouverte'),
            self::WAITLIST => Translation::translate('Liste d\'attente'),
            self::CLOSED => Translation::translate('Fermée'),
        };
    }
}