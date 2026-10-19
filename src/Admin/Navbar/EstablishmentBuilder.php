<?php

namespace App\Admin\Navbar;

use App\Shared\Helper\Translation;

class EstablishmentBuilder extends AbstractNavbarBuilder
{
    public function getKey(): string
    {
        return 'establishments';
    }

    public function getLabel(): string
    {
        return Translation::translate('Établissements');
    }

    public function getRoute(): string
    {
        return 'admin_establishments';
    }

    public function getRole(): string
    {
        return 'ROLE_MODERATOR';
    }

    public function getIcon(): string
    {
        return 'store';
    }

    public function getPosition(): int
    {
        return 60;
    }
}