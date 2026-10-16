<?php

namespace App\Billing\Task;

use App\Billing\Service\RenewalService;
use DateTimeImmutable;
use NeoPHP\Package\Scheduler\Attribute\AsScheduledTask;

#[AsScheduledTask(
    cron: '10 * * * *',
    name: 'billing-renew-subscriptions',
    withoutOverlapping: true,
    description: 'Renouvelle les abonnements arrivés à échéance et émet leurs factures'
)]
class RenewSubscriptionsTask
{
    public function __invoke(RenewalService $renewals): string
    {
        return sprintf(
            '%d abonnement(s) renouvelé(s)',
            $renewals->renewDue(new DateTimeImmutable())
        );
    }
}