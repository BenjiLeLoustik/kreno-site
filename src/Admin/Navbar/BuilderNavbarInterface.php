<?php

namespace App\Admin\Navbar;

interface BuilderNavbarInterface
{
    public function getKey(): string;

    public function getLabel(): string;

    public function getRoute(): string;

    public function getRole(): string;

    public function getIcon(): string;

    public function getPosition(): int;

    public function getBadge(): int;
}