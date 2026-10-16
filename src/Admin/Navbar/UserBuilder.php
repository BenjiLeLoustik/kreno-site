<?php

namespace App\Admin\Navbar;

class UserBuilder extends AbstractNavbarBuilder
{
    public function getKey(): string
    {
        return 'users';
    }

    public function getLabel(): string
    {
        return 'Utilisateurs';
    }

    public function getRoute(): string
    {
        return 'admin_user_index';
    }

    public function getRole(): string
    {
        return 'ROLE_MODERATOR';
    }

    public function getIcon(): string
    {
        return 'users';
    }

    public function getPosition(): int
    {
        return 50;
    }
}