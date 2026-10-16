<?php

namespace App\Report\Helper;

use App\Entity\Report\Report;
use App\Report\Enum\ReportStatus;

class ReportStatusHelper
{
    public const PRIORITY_LABELS = ['high' => 'Haute', 'mid' => 'Moyenne', 'low' => 'Basse'];

    public static function reference(Report $report): string
    {
        return sprintf('SG-%04d', $report->getId());
    }

    public static function customerLabel(Report $report): string
    {
        return match ($report->getStatus()) {
            ReportStatus::SENT => 'Envoyé',
            ReportStatus::IN_REVIEW, ReportStatus::AWAITING_REPLY => 'En cours',
            ReportStatus::RESOLVED => 'Résolu',
            ReportStatus::DISMISSED => 'Classé',
        };
    }

    public static function proLabel(Report $report): string
    {
        return $report->getStatus()->label();
    }

    public static function adminLabel(Report $report): string
    {
        return match ($report->getStatus()) {
            ReportStatus::SENT, ReportStatus::IN_REVIEW => 'Ouvert',
            ReportStatus::AWAITING_REPLY => 'En attente de l’établissement',
            ReportStatus::DISMISSED => 'Classé',
            ReportStatus::RESOLVED => $report->getResolution() ?? 'Traité',
        };
    }

    public static function kind(Report $report): string
    {
        return match ($report->getStatus()) {
            ReportStatus::SENT => 'sent',
            ReportStatus::IN_REVIEW => 'review',
            ReportStatus::AWAITING_REPLY => 'answer',
            ReportStatus::RESOLVED => 'done',
            ReportStatus::DISMISSED => 'closed',
        };
    }

    public static function isOpen(Report $report): bool
    {
        return !in_array($report->getStatus(), [ReportStatus::RESOLVED, ReportStatus::DISMISSED], true);
    }
}