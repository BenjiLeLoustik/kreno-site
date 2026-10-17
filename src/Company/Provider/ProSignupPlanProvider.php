<?php

namespace App\Company\Provider;

use App\Billing\Enum\BillingPeriod;
use App\Billing\Gateway\PaymentGatewayInterface;
use App\Billing\Presenter\PlanPresenter;
use App\Billing\Service\BillingInfoService;
use App\Billing\Service\PlanCatalog;
use App\Billing\Service\PriceCalculator;
use App\Billing\Service\PromoCodeService;
use App\Entity\Company\Company;
use App\Entity\Company\CompanySubscription;
use App\Entity\User\User;
use App\Entity\Website\WebsitePromoCode;
use App\Repository\Company\CompanyPaymentMethodRepository;
use App\Repository\Company\CompanySubscriptionRepository;
use App\Website\Enum\PlanBilling;

class ProSignupPlanProvider
{
    public function __construct(
        private PaymentGatewayInterface $gateway,
        private BillingInfoService $billingInfo,
        private PlanCatalog $planCatalog,
        private PriceCalculator $prices,
        private PromoCodeService $promos,
        private CompanySubscriptionRepository $subscriptions,
        private CompanyPaymentMethodRepository $methods,
        private PlanPresenter $planPresenter,
    ) {
    }

    public function plan(Company $company, ?User $user, ?BillingPeriod $period = null): array
    {
        $pending = $this->subscriptions->findPending($company);
        $period ??= $pending?->getPeriod() ?? BillingPeriod::MONTHLY;
        $plans = $this->planCatalog->plans();

        return [
            'period' => $period->value,
            'periods' => $this->planPresenter->periods($plans, $period),
            'plans' => $this->planPresenter->offers($plans, $period, null, $pending?->getPlan()?->getCode()),
            'selected' => $this->selected($pending),
            'establishmentsCount' => $company->getEstablishments()->count(),
            'billing' => $this->billing($company, $user),
            'hasPaymentMethod' => $this->methods->findForCompany($company) !== [],
            'gatewayKey' => $this->gateway->getPublicKey(),
        ];
    }

    private function selected(?CompanySubscription $pending): ?array
    {
        $plan = $pending?->getPlan();

        if ($pending === null || $plan === null) {
            return null;
        }

        return [
            'code' => $plan->getCode(),
            'name' => $plan->getName(),
            'period' => $pending->getPeriod()->value,
            'perBooking' => $plan->getBilling() === PlanBilling::PER_BOOKING,
            'quote' => $this->prices->quote($plan, $pending->getPeriod(), $pending->getPromoCode()),
            'promo' => $this->promo($pending->getPromoCode()),
        ];
    }

    private function promo(?WebsitePromoCode $promo): ?array
    {
        if ($promo === null) {
            return null;
        }

        return ['code' => $promo->getCode(), 'label' => $this->promos->describe($promo)];
    }

    private function billing(Company $company, ?User $user): array
    {
        $config = $company->getConfig();

        return [
            'billingName' => $this->billingInfo->billingName($company),
            'billingEmail' => $this->billingInfo->billingEmail($company) ?? $user?->getEmail(),
            'vatNumber' => $company->getVatNumber(),
            'addressLine1' => $config?->getAddressLine1() ?? $company->getAddressLine1(),
            'addressLine2' => $config?->getAddressLine2() ?? $company->getAddressLine2(),
            'postalCode' => $config?->getPostalCode() ?? $company->getPostalCode(),
            'locality' => $config?->getLocality() ?? $company->getLocality(),
        ];
    }
}