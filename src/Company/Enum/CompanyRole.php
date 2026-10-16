<?php

declare(strict_types=1);

namespace App\Company\Enum;

enum CompanyRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Propriétaire',
            self::ADMIN => 'Administrateur',
        };
    }
}