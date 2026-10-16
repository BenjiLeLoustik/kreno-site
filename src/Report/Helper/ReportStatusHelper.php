<?php

namespace App\Report\Helper;

use App\Entity\Report\Report;
use App\Report\Enum\ReportStatus;

class ReportStatusHelper
{
    public static function reference(Report $report): string
    {
        return sprintf('SG-%04d', $report->getId());
    }

    public static function customerLabel(Report $report): string
    {
        return $report->getStatus()->customerLabel();
    }

    public static function proLabel(Report $report): string
    {
        return $report->getStatus()->label();
    }

    public static function adminLabel(Report $report): string
    {
        if ($report->getStatus() === ReportStatus::RESOLVED && $report->getResolution() !== null) {
            return $report->getResolution();
        }

        return $report->getStatus()->adminLabel();
    }

    public static function kind(Report $report): string
    {
        return $report->getStatus()->kind();
    }

    public static function isOpen(Report $report): bool
    {
        return $report->getStatus()->isOpen();
    }
}