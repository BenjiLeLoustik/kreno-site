<?php

declare(strict_types=1);

namespace App\Company\Enum;

use App\Shared\Helper\Translation;

enum CompanyKind: string
{
    case SOLO = 'solo';
    case TEAM = 'team';

    public function label(): string
    {
        return match ($this) {
            self::SOLO => Translation::translate('Indépendant'),
            self::TEAM => Translation::translate('Avec une équipe'),
        };
    }
}