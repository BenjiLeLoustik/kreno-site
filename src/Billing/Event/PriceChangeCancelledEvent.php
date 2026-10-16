<?php

namespace App\Billing\Event;

use App\Entity\Company\CompanySubscription;
use DateTimeImmutable;

class PriceChangeCancelledEvent
{
    public function __construct(
        private CompanySubscription $subscription,
        private int $announcedPrice,
        private DateTimeImmutable $effectiveAt,
    ) {
    }

    public function getSubscription(): CompanySubscription
    {
        return $this->subscription;
    }

    public function getAnnouncedPrice(): int
    {
        return $this->announcedPrice;
    }

    public function getEffectiveAt(): DateTimeImmutable
    {
        return $this->effectiveAt;
    }
}