<?php

namespace App\Admin\Navbar;

class BillingBuilder extends AbstractNavbarBuilder
{
    public function getKey(): string
    {
        return 'billing';
    }

    public function getLabel(): string
    {
        return 'Facturation';
    }

    public function getRoute(): string
    {
        return 'admin_billing';
    }

    public function getIcon(): string
    {
        return 'receipt';
    }

    public function getPosition(): int
    {
        return 80;
    }
}