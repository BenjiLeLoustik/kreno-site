<?php

namespace App\User\Navbar;

use App\Entity\User\User;
use App\Shared\Navbar\NavbarBuilderInterface;

interface AccountNavbarInterface extends NavbarBuilderInterface
{
    public function getLabel(): string;

    public function getRoute(): string;

    public function getIcon(): string;

    public function getBadge(User $user): int;
}