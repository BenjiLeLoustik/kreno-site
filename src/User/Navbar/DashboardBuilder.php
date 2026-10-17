<?php

namespace App\User\Navbar;

class DashboardBuilder extends AbstractAccountNavbarBuilder
{
    public function getKey(): string
    {
        return 'dashboard';
    }

    public function getLabel(): string
    {
        return 'Tableau de bord';
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