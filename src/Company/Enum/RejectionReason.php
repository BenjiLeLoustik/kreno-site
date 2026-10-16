<?php

namespace App\Company\Enum;

enum RejectionReason: string
{
    case SIRET = 'siret';
    case ADDRESS = 'address';
    case ACTIVITY = 'activity';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SIRET => 'SIRET inactif ou introuvable',
            self::ADDRESS => 'Adresse ne correspondant pas',
            self::ACTIVITY => 'Activité non éligible',
            self::OTHER => 'Autre',
        };
    }
}