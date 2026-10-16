<?php

namespace App\Billing\Presenter;

use App\Billing\Enum\BillingPeriod;
use App\Billing\Service\PriceCalculator;
use App\Entity\Company\CompanySubscription;
use App\Entity\Website\WebsitePlan;
use App\Entity\Website\WebsitePromoCode;
use App\Shared\Helper\MoneyHelper;
use App\Website\Enum\PlanBilling;

class PlanPresenter
{
    public function __construct(
        private PriceCalculator $prices,
    ) {
    }

    public function offers(array $plans, BillingPeriod $period, ?CompanySubscription $current = null, ?string $selected = null): array
    {
        return array_values(array_map(fn (WebsitePlan $plan): array => $this->offer($plan, $period, $current, $selected), $plans));
    }

    public function offer(WebsitePlan $plan, BillingPeriod $period, ?CompanySubscription $current = null, ?string $selected = null): array
    {
        $perBooking = $plan->getBilling() === PlanBilling::PER_BOOKING;
        $price = $this->prices->planPrice($plan, $period);

        return [
            'code' => $plan->getCode(),
            'name' => $plan->getName(),
            'tag' => $plan->isFeatured() ? 'Le plus choisi' : null,
            'text' => $plan->getDescription(),
            'billing' => $plan->getBilling()->value,
            'perBooking' => $perBooking,
            'price' => MoneyHelper::format($price),
            'priceCents' => $price,
            'unit' => match (true) {
                $perBooking => 'HT par créneau réservé',
                $period === BillingPeriod::YEARLY => sprintf('HT par an, soit %s / mois', MoneyHelper::formatExact((int) round($price / 12))),
                default => 'HT par mois',
            },
            'priceIncludingTax' => MoneyHelper::formatExact(MoneyHelper::includingTax($price, $plan->getVatRate())),
            'perks' => $this->perks($plan),
            'maxEstablishments' => $plan->getMaxEstablishments(),
            'trialDays' => $plan->getTrialDays(),
            'current' => $current !== null && $current->getPlan()?->getCode() === $plan->getCode() && ($perBooking || $current->getPeriod() === $period),
            'selected' => $selected === $plan->getCode(),
        ];
    }

    public function periods(array $plans, BillingPeriod $selected): array
    {
        $offered = 0;

        foreach ($plans as $plan) {
            if ($plan instanceof WebsitePlan && $plan->getBilling() === PlanBilling::SUBSCRIPTION && $plan->getPrice() > 0) {
                $offered = max($offered, 12 - (int) round($this->prices->planPrice($plan, BillingPeriod::YEARLY) / $plan->getPrice()));
            }
        }

        return [
            ['key' => BillingPeriod::MONTHLY->value, 'label' => 'Mensuel', 'selected' => $selected === BillingPeriod::MONTHLY],
            ['key' => BillingPeriod::YEARLY->value, 'label' => $offered > 0 ? sprintf('Annuel · %d mois offerts', $offered) : 'Annuel', 'selected' => $selected === BillingPeriod::YEARLY],
        ];
    }

    public function perks(WebsitePlan $plan): array
    {
        if ($plan->getBilling() === PlanBilling::PER_BOOKING) {
            return ['Aucun abonnement', 'Créneaux illimités', 'Facturé en fin de mois'];
        }

        $perks = [];

        if ($plan->isCommissionFree()) {
            $perks[] = 'Sans commission';
        }

        if ($plan->isFeatured()) {
            $perks[] = 'Mise en avant dans le quartier';
        }

        $perks[] = $plan->getMaxEstablishments() === null ? 'Établissements illimités' : sprintf('%d établissement%s', $plan->getMaxEstablishments(), $plan->getMaxEstablishments() > 1 ? 's' : '');

        if ($plan->isAdvancedStats()) {
            $perks[] = 'Statistiques détaillées';
        }

        if ($plan->getTrialDays() > 0) {
            $perks[] = sprintf('%d jours d\'essai', $plan->getTrialDays());
        }

        $perks[] = $plan->getCommitmentMonths() > 0 ? sprintf('Engagement %d mois', $plan->getCommitmentMonths()) : 'Sans engagement';

        return $perks;
    }

    public function promo(?WebsitePromoCode $promo, string $description): ?array
    {
        return $promo === null ? null : ['code' => $promo->getCode(), 'label' => $description];
    }
}