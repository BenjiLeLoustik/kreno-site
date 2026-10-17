<?php

namespace App\Company\Navbar;

class CompanyBuilder extends AbstractProNavbarBuilder
{
    public function getKey(): string
    {
        return 'company';
    }

    public function getLabel(): string
    {
        return 'Mon compte';
    }

    public function getRoute(): string
    {
        return 'pro_company';
    }

    public function getIcon(): string
    {
        return 'building-2';
    }

    public function getPosition(): int
    {
        return 110;
    }

    public function getPermission(): ?string
    {
        return null;
    }
}