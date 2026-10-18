<?php

namespace App\Company\Navbar;

use App\Company\Security\Permission;
use App\Shared\Helper\Translation;

class TeamBuilder extends AbstractProNavbarBuilder
{
    public function getKey(): string
    {
        return 'team';
    }

    public function getLabel(): string
    {
        return Translation::translate('Équipe');
    }

    public function getRoute(): string
    {
        return 'pro_team';
    }

    public function getIcon(): string
    {
        return 'users';
    }

    public function getPosition(): int
    {
        return 90;
    }

    public function getPermission(): ?string
    {
        return Permission::MEMBERS_MANAGE;
    }

    public function getScope(): ProNavbarScope
    {
        return ProNavbarScope::EITHER;
    }
}