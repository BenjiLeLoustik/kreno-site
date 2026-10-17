<?php

namespace App\User\Navbar;

class AlertBuilder extends AbstractAccountNavbarBuilder
{
    public function getKey(): string
    {
        return 'alerts';
    }

    public function getLabel(): string
    {
        return 'Mes alertes';
    }

    public function getRoute(): string
    {
        return 'account_alerts';
    }

    public function getIcon(): string
    {
        return 'bell-ring';
    }

    public function getPosition(): int
    {
        return 40;
    }
}