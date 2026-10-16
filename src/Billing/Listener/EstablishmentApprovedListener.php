<?php

namespace App\Billing\Listener;

use App\Billing\Service\SubscriptionService;
use App\Company\Event\EstablishmentApprovedEvent;
use NeoPHP\Component\Event\Attribute\AsListener;

class EstablishmentApprovedListener
{
    public function __construct(
        private SubscriptionService $subscriptions,
    ) {
    }

    #[AsListener(EstablishmentApprovedEvent::class)]
    public function onApproved(EstablishmentApprovedEvent $event): void
    {
        $company = $event->establishment->getCompany();

        if ($company !== null) {
            $this->subscriptions->activatePending($company);
        }
    }
}