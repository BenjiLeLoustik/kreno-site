<?php

namespace App\Company\Navbar;

use App\Company\Security\Permission;

class AgendaBuilder extends AbstractProNavbarBuilder
{
    public function getKey(): string
    {
        return 'agenda';
    }

    public function getLabel(): string
    {
        return 'Agenda';
    }

    public function getRoute(): string
    {
        return 'pro_agenda';
    }

    public function getIcon(): string
    {
        return 'calendar-days';
    }

    public function getPosition(): int
    {
        return 40;
    }

    public function getPermission(): ?string
    {
        return Permission::PLANNING_VIEW;
    }
}