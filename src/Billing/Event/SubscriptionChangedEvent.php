<?php

namespace App\Billing\Event;

use App\Entity\Company\CompanySubscription;

class SubscriptionChangedEvent
{
    public function __construct(
        private CompanySubscription $subscription,
        private ?CompanySubscription $previous = null,
    ) {
    }

    public function getSubscription(): CompanySubscription
    {
        return $this->subscription;
    }

    public function getPrevious(): ?CompanySubscription
    {
        return $this->previous;
    }
}