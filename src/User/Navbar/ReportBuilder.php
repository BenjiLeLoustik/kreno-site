<?php

namespace App\User\Navbar;

use App\Shared\Helper\Translation;

class ReportBuilder extends AbstractAccountNavbarBuilder
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
        return 'account_reports';
    }

    public function getIcon(): string
    {
        return 'flag';
    }

    public function getPosition(): int
    {
        return 80;
    }
}