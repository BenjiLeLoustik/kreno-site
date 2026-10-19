<?php

namespace App\Report\Service;

use App\Entity\Report\Report;
use App\Entity\User\User;
use App\Report\Enum\ReportDirection;

class EstablishmentReportMessageService
{
    public function __construct(
        private ReportService $reports,
    ) {
    }

    public function send(Report $report, User $author, string $body): void
    {
        if ($report->getDirection() === ReportDirection::FROM_CUSTOMER) {
            $this->reports->replyAsEstablishment($report, $author, $body);

            return;
        }

        $this->reports->post($report, $author, $body);
    }
}