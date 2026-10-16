<?php

declare(strict_types=1);

namespace App\Establishment\Enum;

enum EstablishmentRole: string
{
    case MANAGER = 'manager';
    case PLANNER = 'planner';
    case RECEPTIONIST = 'receptionist';
    case EMPLOYEE = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::MANAGER => 'Gérant',
            self::PLANNER => 'Planificateur',
            self::RECEPTIONIST => 'Réceptionniste',
            self::EMPLOYEE => 'Employé',
        };
    }
}