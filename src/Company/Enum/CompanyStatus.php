<?php

declare(strict_types=1);

namespace App\Company\Enum;

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
            self::ONBOARDING => 'Inscription en cours',
            self::PENDING_REVIEW => 'En vérification',
            self::ACTIVE => 'Active',
            self::REJECTED => 'Refusée',
            self::SUSPENDED => 'Suspendue',
        };
    }
}