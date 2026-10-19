<?php

namespace App\Report\Task;

use App\Report\Service\ReportService;
use DateTimeImmutable;
use NeoPHP\Package\Scheduler\Attribute\AsScheduledTask;

#[AsScheduledTask(
    cron: '40 * * * *',
    name: 'report-escalate-unanswered',
    withoutOverlapping: true,
    description: 'Passe en examen les signalements restés sans réponse de l\'établissement après 72 h'
)]
class EscalateUnansweredReportsTask
{
    public function __invoke(ReportService $reports): string
    {
        return sprintf(
            '%d signalement(s) passé(s) en examen',
            $reports->escalateUnanswered(new DateTimeImmutable())
        );
    }
}