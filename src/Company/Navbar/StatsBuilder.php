<?php

namespace App\Company\Navbar;

use App\Company\Security\Permission;

class StatsBuilder extends AbstractProNavbarBuilder
{
    public function getKey(): string
    {
        return 'stats';
    }

    public function getLabel(): string
    {
        return 'Statistiques';
    }

    public function getRoute(): string
    {
        return 'pro_stats';
    }

    public function getIcon(): string
    {
        return 'chart-column';
    }

    public function getPosition(): int
    {
        return 60;
    }

    public function getPermission(): ?string
    {
        return Permission::PLANNING_VIEW;
    }
}