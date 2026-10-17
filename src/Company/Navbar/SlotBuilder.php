<?php

namespace App\Company\Navbar;

use App\Company\Security\Permission;

class SlotBuilder extends AbstractProNavbarBuilder
{
    public function getKey(): string
    {
        return 'slots';
    }

    public function getLabel(): string
    {
        return 'Mes créneaux';
    }

    public function getRoute(): string
    {
        return 'pro_slots';
    }

    public function getIcon(): string
    {
        return 'list';
    }

    public function getPosition(): int
    {
        return 30;
    }

    public function getPermission(): ?string
    {
        return Permission::PLANNING_VIEW;
    }
}