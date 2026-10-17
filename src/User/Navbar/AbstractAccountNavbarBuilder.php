<?php

namespace App\User\Navbar;

use App\Entity\User\User;

abstract class AbstractAccountNavbarBuilder implements AccountNavbarInterface
{
    public function getBadge(User $user): int
    {
        return 0;
    }
}