<?php

namespace App\Admin\Navbar;

class PricingBuilder extends AbstractNavbarBuilder
{
    public function getKey(): string
    {
        return 'pricing';
    }

    public function getLabel(): string
    {
        return 'Tarifs et formules';
    }

    public function getRoute(): string
    {
        return 'admin_pricing_index';
    }

    public function getIcon(): string
    {
        return 'tags';
    }

    public function getPosition(): int
    {
        return 100;
    }
}