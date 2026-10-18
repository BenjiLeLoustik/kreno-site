<?php

namespace App\Admin\Navbar;

use App\Shared\Helper\Translation;

class VersionBuilder extends AbstractNavbarBuilder
{
    public function getKey(): string
    {
        return 'versions';
    }

    public function getLabel(): string
    {
        return Translation::translate('Versions du site');
    }

    public function getRoute(): string
    {
        return 'admin_website_index';
    }

    public function getIcon(): string
    {
        return 'git-branch';
    }

    public function getPosition(): int
    {
        return 110;
    }
}