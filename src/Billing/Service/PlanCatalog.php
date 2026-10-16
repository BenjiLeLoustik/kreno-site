<?php

namespace App\Billing\Service;

use App\Entity\Website\WebsitePlan;
use App\Shared\Exception\BusinessRuleException;
use App\Website\Provider\WebsiteProvider;
use App\Website\Enum\PlanBilling;

class PlanCatalog
{
    public function __construct(
        private WebsiteProvider $website,
    ) {
    }

    public function plans(): array
    {
        return $this->website->getPlans();
    }

    public function find(string $code): ?WebsitePlan
    {
        foreach ($this->plans() as $plan) {
            if ($plan->getCode() === $code) {
                return $plan;
            }
        }

        return null;
    }

    public function get(string $code): WebsitePlan
    {
        return $this->find($code) ?? throw new BusinessRuleException('Cette formule n\'est pas proposée.');
    }

    public function defaultPerBooking(): WebsitePlan
    {
        $plans = array_filter($this->plans(), static fn (WebsitePlan $plan): bool => $plan->getBilling() === PlanBilling::PER_BOOKING);
        usort($plans, static fn (WebsitePlan $a, WebsitePlan $b): int => $a->getPrice() <=> $b->getPrice());

        return $plans[0] ?? throw new BusinessRuleException('Aucune formule « à la réservation » n\'est proposée.');
    }
}