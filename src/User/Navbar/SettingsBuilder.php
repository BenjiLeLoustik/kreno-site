<?php

namespace App\User\Navbar;

class SettingsBuilder extends AbstractAccountNavbarBuilder
{
    public function getKey(): string
    {
        return 'settings';
    }

    public function getLabel(): string
    {
        return 'Réglages';
    }

    public function getRoute(): string
    {
        return 'account_settings';
    }

    public function getIcon(): string
    {
        return 'settings';
    }

    public function getPosition(): int
    {
        return 90;
    }
}