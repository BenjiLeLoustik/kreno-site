<?php

namespace App\Admin\Navbar;

class SettingsBuilder extends AbstractNavbarBuilder
{
    public function getKey(): string
    {
        return 'settings';
    }

    public function getLabel(): string
    {
        return 'Réglages et journal';
    }

    public function getRoute(): string
    {
        return 'admin_settings_index';
    }

    public function getIcon(): string
    {
        return 'settings';
    }

    public function getPosition(): int
    {
        return 130;
    }
}