<?php

namespace App\Company\Enum;

use App\Company\Security\Permission;
use App\Shared\Helper\Translation;

enum TeamMatrixAction: string
{
    case COMPANY_EDIT = Permission::COMPANY_EDIT;
    case ESTABLISHMENT_CREATE = Permission::ESTABLISHMENT_CREATE;
    case MEMBERS_MANAGE = Permission::MEMBERS_MANAGE;
    case SERVICES_MANAGE = Permission::SERVICES_MANAGE;
    case SLOTS_MANAGE = Permission::SLOTS_MANAGE;
    case BOOKINGS_MANAGE = Permission::BOOKINGS_MANAGE;
    case PLANNING_VIEW = Permission::PLANNING_VIEW;

    public function label(): string
    {
        return match ($this) {
            self::COMPANY_EDIT => Translation::translate('Modifier l\'entreprise'),
            self::ESTABLISHMENT_CREATE => Translation::translate('Créer un établissement'),
            self::MEMBERS_MANAGE => Translation::translate('Gérer les membres'),
            self::SERVICES_MANAGE => Translation::translate('Gérer les prestations'),
            self::SLOTS_MANAGE => Translation::translate('Gérer les créneaux'),
            self::BOOKINGS_MANAGE => Translation::translate('Gérer les réservations'),
            self::PLANNING_VIEW => Translation::translate('Consulter le planning'),
        };
    }
}