<?php

namespace App\Admin\Navbar;

use App\Enum\Log\AppLogStatus;
use App\Repository\AppLogRepository;

class ErrorBuilder extends AbstractNavbarBuilder
{
    public function __construct(
        private AppLogRepository $logs,
    ) {
    }

    public function getKey(): string
    {
        return 'errors';
    }

    public function getLabel(): string
    {
        return 'Erreurs du site';
    }

    public function getRoute(): string
    {
        return 'admin_app_logs';
    }

    public function getIcon(): string
    {
        return 'bug';
    }

    public function getPosition(): int
    {
        return 120;
    }

    public function getBadge(): int
    {
        return $this->logs->countByStatus(AppLogStatus::NEW);
    }
}