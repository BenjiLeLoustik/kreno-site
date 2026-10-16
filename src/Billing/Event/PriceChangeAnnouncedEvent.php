<?php

namespace App\Billing\Event;

use App\Entity\Company\CompanySubscription;
use App\Entity\Website\WebsitePlan;
use DateTimeImmutable;

class PriceChangeAnnouncedEvent
{
    public function __construct(
        private CompanySubscription $subscription,
        private WebsitePlan $newPlan,
        private int $currentPrice,
        private int $newPrice,
        private DateTimeImmutable $effectiveAt,
        private ?int $previousNewPrice = null,
        private ?DateTimeImmutable $previousEffectiveAt = null,
    ) {
    }

    public function getSubscription(): CompanySubscription
    {
        return $this->subscription;
    }

    public function getNewPlan(): WebsitePlan
    {
        return $this->newPlan;
    }

    public function getCurrentPrice(): int
    {
        return $this->currentPrice;
    }

    public function getNewPrice(): int
    {
        return $this->newPrice;
    }

    public function getEffectiveAt(): DateTimeImmutable
    {
        return $this->effectiveAt;
    }

    public function getPreviousNewPrice(): ?int
    {
        return $this->previousNewPrice;
    }

    public function getPreviousEffectiveAt(): ?DateTimeImmutable
    {
        return $this->previousEffectiveAt;
    }

    public function isCorrection(): bool
    {
        return $this->previousNewPrice !== null;
    }

    public function correct(int $previousNewPrice, DateTimeImmutable $previousEffectiveAt): self
    {
        return new self($this->subscription, $this->newPlan, $this->currentPrice, $this->newPrice, $this->effectiveAt, $previousNewPrice, $previousEffectiveAt);
    }
}