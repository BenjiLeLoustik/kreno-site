<?php

namespace App\User\Navbar;

use App\Shared\Helper\Translation;

class DashboardBuilder extends AbstractAccountNavbarBuilder
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
        return 'account_index';
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