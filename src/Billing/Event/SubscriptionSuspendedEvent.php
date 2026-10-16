<?php

namespace App\Billing\Event;

use App\Entity\Company\CompanySubscription;

class SubscriptionSuspendedEvent
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