<?php

declare(strict_types=1);

namespace App\Establishment\Enum;

use App\Shared\Helper\Translation;

enum EstablishmentRole: string
{
    case MANAGER = 'manager';
    case PLANNER = 'planner';
    case RECEPTIONIST = 'receptionist';
    case EMPLOYEE = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::MANAGER => Translation::translate('Gérant'),
            self::PLANNER => Translation::translate('Planificateur'),
            self::RECEPTIONIST => Translation::translate('Réceptionniste'),
            self::EMPLOYEE => Translation::translate('Employé'),
        };
    }
}