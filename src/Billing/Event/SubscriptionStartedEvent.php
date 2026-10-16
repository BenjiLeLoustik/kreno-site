<?php

namespace App\Billing\Event;

use App\Entity\Company\CompanySubscription;

class SubscriptionStartedEvent
{
    public function __construct(
        private CompanySubscription $subscription,
    ) {
    }

    public function getSubscription(): CompanySubscription
    {
        return $this->subscription;
    }
}