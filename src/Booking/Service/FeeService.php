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
    public const BILLABLE_STATUSES = [
        SubscriptionStatus::ACTIVE,
        SubscriptionStatus::PAST_DUE
    ];

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
            if ($plan->isCommissionFree()) {
                return 0;
            }

            return $subscription->getUnitPrice();
        }

        if ($this->isCovered($subscription, $establishment)) {
            return 0;
        }

        return $this->catalog->defaultPerBooking()->getPrice();
    }

    public function currentSubscription(CompanyEstablishment $establishment): ?CompanySubscription
    {
        $company = $establishment->getCompany();

        if ($company === null) {
            return null;
        }

        $subscription = $this->subscriptions->findCurrent($company);

        if ($subscription === null || !in_array($subscription->getStatus(), self::BILLABLE_STATUSES, true)) {
            return null;
        }

        return $subscription;
    }

    public function isCovered(CompanySubscription $subscription, CompanyEstablishment $establishment): bool
    {
        $company = $subscription->getCompany();

        if ($subscription->getPlan()?->getBilling() !== PlanBilling::SUBSCRIPTION || $company === null) {
            return false;
        }

        $establishments = $this->subscriptionService->billableEstablishments($company);
        $index = array_search($establishment, $establishments, true);

        return $index !== false && $this->subscriptionService->covers($subscription, $index);
    }
}