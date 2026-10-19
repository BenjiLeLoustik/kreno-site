<?php

declare(strict_types=1);

namespace App\Company\Enum;

use App\Shared\Helper\Translation;

enum CompanyStatus: string
{
    case ONBOARDING = 'onboarding';
    case PENDING_REVIEW = 'pending_review';
    case ACTIVE = 'active';
    case REJECTED = 'rejected';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::ONBOARDING => Translation::translate('Inscription en cours'),
            self::PENDING_REVIEW => Translation::translate('En vérification'),
            self::ACTIVE => Translation::translate('Active'),
            self::REJECTED => Translation::translate('Refusée'),
            self::SUSPENDED => Translation::translate('Suspendue'),
        };
    }
}