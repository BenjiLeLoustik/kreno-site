<?php

namespace App\Admin\Navbar;

class VersionBuilder extends AbstractNavbarBuilder
{
    public function getKey(): string
    {
        return 'versions';
    }

    public function getLabel(): string
    {
        return 'Versions du site';
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