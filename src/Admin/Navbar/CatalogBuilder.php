<?php

namespace App\Admin\Navbar;

class CatalogBuilder extends AbstractNavbarBuilder
{
    public function getKey(): string
    {
        return 'catalog';
    }

    public function getLabel(): string
    {
        return 'Catégories et villes';
    }

    public function getRoute(): string
    {
        return 'admin_catalog_index';
    }

    public function getIcon(): string
    {
        return 'map-pin';
    }

    public function getPosition(): int
    {
        return 90;
    }
}