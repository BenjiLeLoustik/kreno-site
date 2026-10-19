<?php

namespace App\Billing\Task;

use App\Billing\Service\CommissionBillingService;
use DateTimeImmutable;
use NeoPHP\Package\Scheduler\Attribute\AsScheduledTask;

#[AsScheduledTask(
    cron: '0 3 1 * *',
    name: 'billing-invoice-commissions',
    withoutOverlapping: true,
    description: 'Facture les créneaux réservés du mois précédent'
)]
class InvoiceCommissionsTask
{
    public function __invoke(CommissionBillingService $commissions): string
    {
        return sprintf(
            '%d facture(s) de commissions émise(s)',
            $commissions->invoiceMonth((new DateTimeImmutable())->modify('first day of previous month'))
        );
    }
}