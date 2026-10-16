<?php

namespace App\Report\Event;

use App\Entity\Report\Report;

class ReportDecidedEvent
{
    public function __construct(
        private Report $report,
        private string $decision,
    ) {
    }

    public function getReport(): Report
    {
        return $this->report;
    }

    public function getDecision(): string
    {
        return $this->decision;
    }
}