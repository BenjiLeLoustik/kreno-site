<?php

namespace App\Company\Navbar;

use App\Company\Security\Permission;
use App\Shared\Helper\Translation;

class BillingBuilder extends AbstractProNavbarBuilder
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
        return 'pro_billing';
    }

    public function getIcon(): string
    {
        return 'receipt';
    }

    public function getPosition(): int
    {
        return 100;
    }

    public function getPermission(): ?string
    {
        return Permission::COMPANY_BILLING;
    }

    public function getScope(): ProNavbarScope
    {
        return ProNavbarScope::COMPANY;
    }
}