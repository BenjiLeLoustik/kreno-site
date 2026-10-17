<?php

namespace App\Company\Navbar;

use App\Entity\Company\CompanyEstablishment;
use App\Shared\Navbar\NavbarBuilderInterface;

interface ProNavbarInterface extends NavbarBuilderInterface
{
    public function getLabel(): string;

    public function getRoute(): string;

    public function getIcon(): string;

    public function getPermission(): ?string;

    public function getScope(): ProNavbarScope;

    public function getBadge(CompanyEstablishment $establishment): int;
}