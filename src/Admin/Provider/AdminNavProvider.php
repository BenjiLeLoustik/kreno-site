<?php

namespace App\Admin\Provider;

use App\Enum\Log\AppLogStatus;
use App\Establishment\Enum\EstablishmentStatus;
use App\Repository\AppLogRepository;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\Repository\Report\ReportRepository;

class AdminNavProvider
{
    public function __construct(
        private CompanyEstablishmentRepository $establishments,
        private ReportRepository $reports,
        private AppLogRepository $logs,
    ) {
    }

    public function badges(): array
    {
        return [
            'moderation' => $this->establishments->countByStatus(EstablishmentStatus::PENDING_REVIEW),
            'reports' => $this->reports->countOpen(),
            'errors' => $this->logs->countByStatus(AppLogStatus::NEW),
        ];
    }
}