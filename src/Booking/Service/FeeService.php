<?php

namespace App\Booking\Service;

use App\Billing\Enum\SubscriptionStatus;
use App\Billing\Service\PlanCatalog;
use App\Billing\Service\SubscriptionService;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanySubscription;
use App\Repository\Company\CompanySubscriptionRepository;
use App\Website\Enum\PlanBilling;

class FeeService
{
    public const BILLABLE_STATUSES = [SubscriptionStatus::ACTIVE, SubscriptionStatus::PAST_DUE];

    public function __construct(
        private CompanySubscriptionRepository $subscriptions,
        private SubscriptionService $subscriptionService,
        private PlanCatalog $catalog,
    ) {
    }

    public function feeFor(CompanyEstablishment $establishment): int
    {
        $subscription = $this->currentSubscription($establishment);
        $plan = $subscription?->getPlan();

        if ($subscription === null || $plan === null) {
            return 0;
        }

        if ($plan->getBilling() === PlanBilling::PER_BOOKING) {
            return $plan->isCommissionFree() ? 0 : $subscription->getUnitPrice();
        }

        return $this->isCovered($subscription, $establishment) ? 0 : $this->catalog->defaultPerBooking()->getPrice();
    }

    public function currentSubscription(CompanyEstablishment $establishment): ?CompanySubscription
    {
        $company = $establishment->getCompany();
        $subscription = $company !== null ? $this->subscriptions->findCurrent($company) : null;

        return $subscription !== null && in_array($subscription->getStatus(), self::BILLABLE_STATUSES, true) ? $subscription : null;
    }

    public function isCovered(CompanySubscription $subscription, CompanyEstablishment $establishment): bool
    {
        if ($subscription->getPlan()?->getBilling() !== PlanBilling::SUBSCRIPTION || $subscription->getCompany() === null) {
            return false;
        }

        $index = array_search($establishment, $this->subscriptionService->billableEstablishments($subscription->getCompany()), true);

        return $index !== false && $this->subscriptionService->covers($subscription, $index);
    }
}