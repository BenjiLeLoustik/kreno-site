<?php

namespace App\Company\Navbar;

use App\Entity\Company\CompanyEstablishment;

abstract class AbstractProNavbarBuilder implements ProNavbarInterface
{
    public function getScope(): ProNavbarScope
    {
        return ProNavbarScope::ESTABLISHMENT;
    }

    public function getBadge(CompanyEstablishment $establishment): int
    {
        return 0;
    }
}