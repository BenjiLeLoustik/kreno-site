<?php

namespace App\Billing\Task;

use App\Billing\Service\PriceChangeNotifier;
use DateTimeImmutable;
use NeoPHP\Package\Scheduler\Attribute\AsScheduledTask;

#[AsScheduledTask(cron: '20 * * * *', name: 'billing-cancel-stale-price-notices', withoutOverlapping: true, description: 'Annule les changements de prix annoncés pour une version déplanifiée depuis plus de 24 h')]
class CancelStalePriceNoticesTask
{
    public function __invoke(PriceChangeNotifier $notifier): string
    {
        return sprintf('%d annonce(s) de prix annulée(s)', $notifier->cancelStale(new DateTimeImmutable()));
    }
}