<?php

namespace App\Billing\Task;

use App\Billing\Service\BillingAccessService;
use App\Establishment\Service\SlotPublicationService;
use DateTimeImmutable;
use NeoPHP\Package\Scheduler\Attribute\AsScheduledTask;

#[AsScheduledTask(
    cron: '30 * * * *',
    name: 'billing-enforce-grace',
    withoutOverlapping: true,
    description: 'Retire les créneaux des entreprises dont le délai de paiement est dépassé'
)]
class EnforceGraceTask
{
    public function __invoke(BillingAccessService $access, SlotPublicationService $publication): string
    {
        return sprintf(
            '%d entreprise(s) suspendue(s)',
            $access->enforceGrace($publication, new DateTimeImmutable())
        );
    }
}