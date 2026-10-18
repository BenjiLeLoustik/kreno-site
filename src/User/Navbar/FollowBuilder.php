<?php

namespace App\User\Navbar;

use App\Shared\Helper\Translation;

class FollowBuilder extends AbstractAccountNavbarBuilder
{
    public function getKey(): string
    {
        return 'follows';
    }

    public function getLabel(): string
    {
        return Translation::translate('Établissements suivis');
    }

    public function getRoute(): string
    {
        return 'account_follows';
    }

    public function getIcon(): string
    {
        return 'heart';
    }

    public function getPosition(): int
    {
        return 50;
    }
}