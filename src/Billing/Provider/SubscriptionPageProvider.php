<?php

namespace App\Billing\Provider;

use App\Billing\Enum\BillingPeriod;
use App\Billing\Enum\SubscriptionFlash;
use App\Billing\Exception\NoCurrentSubscriptionException;
use App\Billing\Model\SubscriptionChange;
use App\Billing\Presenter\PlanPresenter;
use App\Billing\Presenter\SubscriptionPresenter;
use App\Billing\Service\PlanCatalog;
use App\Billing\Service\PriceCalculator;
use App\Billing\Service\PromoCodeService;
use App\Billing\Service\SubscriptionService;
use App\Entity\Company\Company;
use App\Entity\Company\CompanySubscription;
use App\Entity\Website\WebsitePlan;
use App\Entity\Website\WebsitePromoCode;
use App\Repository\Company\CompanySubscriptionRepository;
use App\Shared\Exception\BusinessRuleException;
use App\Website\Enum\PlanBilling;
use DateTimeImmutable;

class SubscriptionPageProvider
{
    public function __construct(
        private SubscriptionService $subscriptionService,
        private PlanCatalog $catalog,
        private PriceCalculator $prices,
        private PromoCodeService $promos,
        private CompanySubscriptionRepository $subscriptions,
        private BillingPageProvider $billing,
        private PlanPresenter $planPresenter,
        private SubscriptionPresenter $subscriptionPresenter,
    ) {
    }

    public function index(Company $company, ?BillingPeriod $period): array
    {
        $current = $this->subscriptions->findCurrent($company);
        $period ??= $current?->getPeriod() ?? BillingPeriod::MONTHLY;
        $plans = $this->catalog->plans();

        return [
            'current' => $this->summary($current),
            'period' => $period->value,
            'periods' => $this->planPresenter->periods($plans, $period),
            'plans' => $this->planPresenter->offers($plans, $period, $current, $current?->getPlan()?->getCode()),
            'establishmentsCount' => count($this->subscriptionService->billableEstablishments($company)),
        ];
    }

    public function quote(Company $company, string $code, ?string $promoCode, ?BillingPeriod $period): array
    {
        $plan = $this->catalog->get($code);
        $current = $this->subscriptions->findCurrent($company);
        $period = $this->quotePeriod($plan, $period);
        [$promo, $promoError] = $this->promo($promoCode, $plan);
        $perBooking = $plan->getBilling() === PlanBilling::PER_BOOKING;

        return [
            'current' => $this->summary($current),
            'plan' => $this->planPresenter->offer($plan, $period, $current),
            'period' => $period->value,
            'quote' => $this->prices->quote($plan, $period, $promo),
            'promoCode' => $promoCode,
            'promo' => $this->promoSummary($promo),
            'promoError' => $promoError,
            'prorata' => $this->quoteProrata($current, $plan, $period, $promo),
            'scheduled' => $current !== null && $perBooking,
            'uncoveredEstablishments' => $this->uncovered($company, $plan),
            'paymentMethods' => $this->billing->usableMethods($company),
        ];
    }

    public function cancelForm(CompanySubscription $subscription): array
    {
        return [
            'current' => $this->subscriptionPresenter->summary($subscription),
            'cancellation' => $this->subscriptionPresenter->cancellation($subscription),
        ];
    }

    public function current(Company $company): CompanySubscription
    {
        $subscription = $this->subscriptions->findCurrent($company);

        if ($subscription === null) {
            throw new NoCurrentSubscriptionException();
        }

        return $subscription;
    }

    public function changeFlash(SubscriptionChange $change): string
    {
        $summary = $this->subscriptionPresenter->summary($change->getSubscription());

        if ($change->isScheduled()) {
            return SubscriptionFlash::CHANGE_SCHEDULED->text(
                $summary['nextDate'] ?? SubscriptionFlash::NEXT_DUE_DATE->text(),
            );
        }

        return SubscriptionFlash::CHANGED->text($summary['plan']['name']);
    }

    public function cancelFlash(CompanySubscription $subscription): string
    {
        $summary = $this->subscriptionPresenter->summary($subscription);

        return SubscriptionFlash::CANCELLED->text($summary['cancelAt'] ?? SubscriptionFlash::PERIOD_END->text());
    }

    private function summary(?CompanySubscription $subscription): ?array
    {
        if ($subscription === null) {
            return null;
        }

        return $this->subscriptionPresenter->summary($subscription);
    }

    private function quotePeriod(WebsitePlan $plan, ?BillingPeriod $period): BillingPeriod
    {
        if ($plan->getBilling() === PlanBilling::PER_BOOKING) {
            return BillingPeriod::MONTHLY;
        }

        return $period ?? BillingPeriod::MONTHLY;
    }

    private function promo(?string $promoCode, WebsitePlan $plan): array
    {
        try {
            return [$this->promos->resolve($promoCode, $plan), null];
        } catch (BusinessRuleException $exception) {
            return [null, $exception->getMessage()];
        }
    }

    private function promoSummary(?WebsitePromoCode $promo): ?array
    {
        if ($promo === null) {
            return null;
        }

        return [
            'code' => $promo->getCode(),
            'label' => $this->promos->describe($promo)
        ];
    }

    private function quoteProrata(
        ?CompanySubscription $current,
        WebsitePlan $plan,
        BillingPeriod $period,
        ?WebsitePromoCode $promo,
    ): ?array {
        if ($current === null || $plan->getBilling() !== PlanBilling::SUBSCRIPTION) {
            return null;
        }

        $prorata = $this->prices->prorata($current, $plan, $period, $promo, new DateTimeImmutable());

        return [
            'until' => $prorata['periodEnd']->format('d/m/Y'),
            'keepsPeriodEnd' => $prorata['keepsPeriodEnd'],
            'charge' => $prorata['chargeLabel'],
            'credit' => $prorata['creditLabel'],
            'due' => $prorata['dueLabel'],
            'dueIsZero' => $prorata['dueIncludingTax'] === 0,
        ];
    }

    private function uncovered(Company $company, WebsitePlan $plan): int
    {
        if ($plan->getBilling() !== PlanBilling::SUBSCRIPTION || $plan->getMaxEstablishments() === null) {
            return 0;
        }

        $establishments = count($this->subscriptionService->billableEstablishments($company));

        return max(0, $establishments - $plan->getMaxEstablishments());
    }
}