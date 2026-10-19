<?php

namespace App\Company\Enum;

use App\Shared\Helper\Translation;

enum RejectionReason: string
{
    case SIRET = 'siret';
    case ADDRESS = 'address';
    case ACTIVITY = 'activity';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SIRET => Translation::translate('SIRET inactif ou introuvable'),
            self::ADDRESS => Translation::translate('Adresse ne correspondant pas'),
            self::ACTIVITY => Translation::translate('Activité non éligible'),
            self::OTHER => Translation::translate('Autre'),
        };
    }
}