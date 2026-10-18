<?php

namespace App\Admin\Navbar;

use App\Repository\Report\ReportRepository;
use App\Shared\Helper\Translation;

class ReportBuilder extends AbstractNavbarBuilder
{
    public function __construct(
        private ReportRepository $reports,
    ) {
    }

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
        return 'admin_reports';
    }

    public function getRole(): string
    {
        return 'ROLE_MODERATOR';
    }

    public function getIcon(): string
    {
        return 'flag';
    }

    public function getPosition(): int
    {
        return 30;
    }

    public function getBadge(): int
    {
        return $this->reports->countOpen();
    }
}