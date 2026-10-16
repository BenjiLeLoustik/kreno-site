<?php

namespace App\Billing\Service;

use App\Billing\Exception\NoPerBookingPlanException;
use App\Billing\Exception\PlanNotOfferedException;
use App\Entity\Website\WebsitePlan;
use App\Website\Enum\PlanBilling;
use App\Website\Provider\WebsiteProvider;

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
        return $this->find($code) ?? throw new PlanNotOfferedException();
    }

    public function defaultPerBooking(): WebsitePlan
    {
        $cheapest = null;

        foreach ($this->plans() as $plan) {
            if ($plan->getBilling() !== PlanBilling::PER_BOOKING) {
                continue;
            }

            if ($cheapest === null || $plan->getPrice() < $cheapest->getPrice()) {
                $cheapest = $plan;
            }
        }

        return $cheapest ?? throw new NoPerBookingPlanException();
    }
}