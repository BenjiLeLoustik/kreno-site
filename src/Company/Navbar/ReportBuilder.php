<?php

namespace App\Company\Navbar;

use App\Company\Security\Permission;
use App\Shared\Helper\Translation;

class ReportBuilder extends AbstractProNavbarBuilder
{
    public function getKey(): string
    {
        return 'reports';
    }

    public function getLabel(): string
    {
        return Translation::translate('Signalements');
    }

    public function getRoute(): string
    {
        return 'pro_reports';
    }

    public function getIcon(): string
    {
        return 'flag';
    }

    public function getPosition(): int
    {
        return 80;
    }

    public function getPermission(): ?string
    {
        return Permission::BOOKINGS_MANAGE;
    }
}