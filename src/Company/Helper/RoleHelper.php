<?php

namespace App\Company\Helper;

use App\Company\Enum\CompanyRole;
use App\Entity\Company\CompanyMember;
use App\Entity\Establishment\EstablishmentMember;
use App\Establishment\Enum\EstablishmentRole;

class RoleHelper
{
    public const LABELS = [
        'owner' => 'Propriétaire',
        'admin' => 'Administrateur',
        'manager' => 'Gérant',
        'planner' => 'Planificateur',
        'receptionist' => 'Réceptionniste',
        'employee' => 'Employé',
    ];

    public static function isCompanyRole(string $role): bool
    {
        return CompanyRole::tryFrom($role) !== null;
    }

    public static function establishmentRole(string $role): ?EstablishmentRole
    {
        return EstablishmentRole::tryFrom($role);
    }

    public static function label(string $role): string
    {
        return self::LABELS[$role] ?? $role;
    }

    public static function describe(CompanyMember $member, ?EstablishmentMember $establishmentRole = null): string
    {
        if ($member->getRole() !== null) {
            return $member->getRole()->value;
        }

        return $establishmentRole?->getRole()->value ?? 'employee';
    }
}