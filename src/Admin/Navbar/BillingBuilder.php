<?php

namespace App\Admin\Navbar;

use App\Shared\Helper\Translation;

class BillingBuilder extends AbstractNavbarBuilder
{
    public function getKey(): string
    {
        return 'billing';
    }

    public function getLabel(): string
    {
        return Translation::translate('Facturation');
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