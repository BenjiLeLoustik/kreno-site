<?php

namespace App\Admin\Navbar;

abstract class AbstractNavbarBuilder implements BuilderNavbarInterface
{
    public function getRole(): string
    {
        return 'ROLE_ADMIN';
    }

    public function getBadge(): int
    {
        return 0;
    }
}