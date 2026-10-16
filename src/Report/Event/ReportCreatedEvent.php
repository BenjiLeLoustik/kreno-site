<?php

namespace App\Report\Event;

use App\Entity\Report\Report;

class ReportCreatedEvent
{
    public function __construct(
        private Report $report,
    ) {
    }

    public function getReport(): Report
    {
        return $this->report;
    }
}