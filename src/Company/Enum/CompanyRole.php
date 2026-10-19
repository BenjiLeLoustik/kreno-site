<?php

declare(strict_types=1);

namespace App\Company\Enum;

use App\Shared\Helper\Translation;

enum CompanyRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => Translation::translate('Propriétaire'),
            self::ADMIN => Translation::translate('Administrateur'),
        };
    }
}