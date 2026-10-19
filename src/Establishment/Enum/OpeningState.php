<?php

namespace App\Establishment\Enum;

use App\Shared\Helper\Translation;

enum OpeningState: string
{
    case OPEN_UNTIL = 'open_until';
    case OPENS_AT = 'opens_at';
    case CLOSED = 'closed';

    public function label(?string $time = null): string
    {
        return match ($this) {
            self::OPEN_UNTIL => sprintf(Translation::translate('Ouvert jusqu\'à %s'), $time),
            self::OPENS_AT => sprintf(Translation::translate('Ouvre à %s'), $time),
            self::CLOSED => Translation::translate('Fermé'),
        };
    }
}