<?php

namespace App\Admin\Navbar;

use App\Shared\Helper\Translation;

class DashboardBuilder extends AbstractNavbarBuilder
{
    public function getKey(): string
    {
        return 'dashboard';
    }

    public function getLabel(): string
    {
        return Translation::translate('Vue d\'ensemble');
    }

    public function getRoute(): string
    {
        return 'admin_dashboard';
    }

    public function getRole(): string
    {
        return 'ROLE_SUPPORT';
    }

    public function getIcon(): string
    {
        return 'layout-dashboard';
    }

    public function getPosition(): int
    {
        return 10;
    }
}