<?php

namespace App\Shared\Navbar;

interface NavbarBuilderInterface
{
    public function getKey(): string;

    public function getPosition(): int;
}