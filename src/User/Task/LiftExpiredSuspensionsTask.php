<?php

namespace App\User\Task;

use App\User\Service\SanctionService;
use DateTimeImmutable;
use NeoPHP\Package\Scheduler\Attribute\AsScheduledTask;

#[AsScheduledTask(
    cron: '*/15 * * * *',
    name: 'user-lift-suspensions',
    withoutOverlapping: true,
    description: 'Réactive les comptes dont la suspension est terminée'
)]
class LiftExpiredSuspensionsTask
{
    public function __invoke(SanctionService $sanctions): string
    {
        return sprintf(
            '%d compte(s) réactivé(s)',
            $sanctions->liftExpired(new DateTimeImmutable())
        );
    }
}