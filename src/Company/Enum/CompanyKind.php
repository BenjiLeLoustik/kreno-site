<?php

declare(strict_types=1);

namespace App\Company\Enum;

enum CompanyKind: string
{
    case SOLO = 'solo';
    case TEAM = 'team';

    public function label(): string
    {
        return match ($this) {
            self::SOLO => 'Indépendant',
            self::TEAM => 'Avec une équipe',
        };
    }
}