<?php

namespace App\Billing\Task;

use App\Billing\Service\PaymentService;
use DateTimeImmutable;
use NeoPHP\Package\Scheduler\Attribute\AsScheduledTask;

#[AsScheduledTask(cron: '20 * * * *', name: 'billing-retry-payments', withoutOverlapping: true, description: 'Relance automatiquement les paiements refusés (J+3 puis J+7)')]
class RetryPaymentsTask
{
    public function __invoke(PaymentService $payments): string
    {
        return sprintf('%d paiement(s) relancé(s)', $payments->retryDue(new DateTimeImmutable()));
    }
}