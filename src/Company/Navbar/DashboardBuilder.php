<?php

namespace App\Company\Navbar;

use App\Company\Security\Permission;
use App\Shared\Helper\Translation;

class DashboardBuilder extends AbstractProNavbarBuilder
{
    public function getKey(): string
    {
        return 'dashboard';
    }

    public function getLabel(): string
    {
        return Translation::translate('Tableau de bord');
    }

    public function getRoute(): string
    {
        return 'pro_dashboard';
    }

    public function getIcon(): string
    {
        return 'layout-dashboard';
    }

    public function getPosition(): int
    {
        return 10;
    }

    public function getPermission(): ?string
    {
        return Permission::PLANNING_VIEW;
    }
}