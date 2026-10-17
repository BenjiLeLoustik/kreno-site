<?php

namespace App\Report\Service;

use App\Entity\Report\Report;
use App\Entity\User\User;
use App\Report\Dto\ReportDecisionInput;
use App\Report\Exception\ReportAlreadyClosedException;
use App\Report\Helper\ReportStatusHelper;

class AdminReportDecisionService
{
    public function __construct(
        private ReportModerationService $moderation,
    ) {
    }

    public function decide(Report $report, User $admin, ReportDecisionInput $input): void
    {
        if (!ReportStatusHelper::isOpen($report)) {
            throw new ReportAlreadyClosedException();
        }

        $this->moderation->decide($report, $admin, $input);
    }
}