<?php

namespace App\Company\Navbar;

use App\Company\Security\Permission;

class EstablishmentBuilder extends AbstractProNavbarBuilder
{
    public function getKey(): string
    {
        return 'establishment';
    }

    public function getLabel(): string
    {
        return 'Mon établissement';
    }

    public function getRoute(): string
    {
        return 'pro_establishment';
    }

    public function getIcon(): string
    {
        return 'store';
    }

    public function getPosition(): int
    {
        return 70;
    }

    public function getPermission(): ?string
    {
        return Permission::ESTABLISHMENT_EDIT;
    }
}