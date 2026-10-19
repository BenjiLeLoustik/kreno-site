<?php

namespace App\Billing\Presenter;

use App\Billing\Enum\BillingPeriod;
use App\Billing\Service\PriceCalculator;
use App\Entity\Company\CompanySubscription;
use App\Entity\Website\WebsitePlan;
use App\Entity\Website\WebsitePromoCode;
use App\Shared\Helper\MoneyHelper;
use App\Shared\Helper\Translation;
use App\Website\Enum\PlanBilling;

class PlanPresenter
{
    public function __construct(
        private PriceCalculator $prices,
    ) {
    }

    public function offers(
        array $plans,
        BillingPeriod $period,
        ?CompanySubscription $current = null,
        ?string $selected = null,
    ): array {
        $offers = [];

        foreach ($plans as $plan) {
            $offers[] = $this->offer($plan, $period, $current, $selected);
        }

        return $offers;
    }

    public function offer(
        WebsitePlan $plan,
        BillingPeriod $period,
        ?CompanySubscription $current = null,
        ?string $selected = null,
    ): array {
        $price = $this->prices->planPrice($plan, $period);

        return [
            'code' => $plan->getCode(),
            'name' => $plan->getName(),
            'tag' => $this->tag($plan),
            'text' => $plan->getDescription(),
            'billing' => $plan->getBilling()->value,
            'perBooking' => $this->isPerBooking($plan),
            'price' => MoneyHelper::format($price),
            'priceCents' => $price,
            'unit' => $this->unit($plan, $period, $price),
            'priceIncludingTax' => MoneyHelper::formatExact(MoneyHelper::includingTax($price, $plan->getVatRate())),
            'perks' => $this->perks($plan),
            'maxEstablishments' => $plan->getMaxEstablishments(),
            'trialDays' => $plan->getTrialDays(),
            'current' => $this->isCurrent($plan, $period, $current),
            'selected' => $selected === $plan->getCode(),
        ];
    }

    public function periods(array $plans, BillingPeriod $selected): array
    {
        $offeredMonths = $this->offeredMonths($plans);
        $periods = [];

        foreach (BillingPeriod::cases() as $period) {
            $periods[] = [
                'key' => $period->value,
                'label' => $this->periodLabel($period, $offeredMonths),
                'selected' => $selected === $period,
            ];
        }

        return $periods;
    }

    public function perks(WebsitePlan $plan): array
    {
        if ($this->isPerBooking($plan)) {
            return [
                Translation::translate('Aucun abonnement'),
                Translation::translate('Créneaux illimités'),
                Translation::translate('Facturé en fin de mois')
            ];
        }

        $perks = [];

        if ($plan->isCommissionFree()) {
            $perks[] = Translation::translate('Sans commission');
        }

        if ($plan->isFeatured()) {
            $perks[] = Translation::translate('Mise en avant dans le quartier');
        }

        $perks[] = $this->establishmentsPerk($plan->getMaxEstablishments());

        if ($plan->isAdvancedStats()) {
            $perks[] = Translation::translate('Statistiques détaillées');
        }

        if ($plan->getTrialDays() > 0) {
            $perks[] = sprintf(
                Translation::translate('%d jours d\'essai'),
                $plan->getTrialDays()
            );
        }

        $perks[] = $this->commitmentPerk($plan->getCommitmentMonths());

        return $perks;
    }

    public function promo(?WebsitePromoCode $promo, string $description): ?array
    {
        if ($promo === null) {
            return null;
        }

        return [
            'code' => $promo->getCode(),
            'label' => $description
        ];
    }

    private function isPerBooking(WebsitePlan $plan): bool
    {
        return $plan->getBilling() === PlanBilling::PER_BOOKING;
    }

    private function tag(WebsitePlan $plan): ?string
    {
        if ($plan->isFeatured()) {
            return Translation::translate('Le plus choisi');
        }

        return null;
    }

    private function unit(WebsitePlan $plan, BillingPeriod $period, int $price): string
    {
        if ($this->isPerBooking($plan)) {
            return Translation::translate('HT par créneau réservé');
        }

        if ($period === BillingPeriod::YEARLY) {
            return sprintf(
                Translation::translate('HT par an, soit %s / mois'),
                MoneyHelper::formatExact((int) round($price / 12))
            );
        }

        return Translation::translate('HT par mois');
    }

    private function isCurrent(WebsitePlan $plan, BillingPeriod $period, ?CompanySubscription $current): bool
    {
        if ($current === null || $current->getPlan()?->getCode() !== $plan->getCode()) {
            return false;
        }

        return $this->isPerBooking($plan) || $current->getPeriod() === $period;
    }

    private function offeredMonths(array $plans): int
    {
        $offered = 0;

        foreach ($plans as $plan) {
            if (!$plan instanceof WebsitePlan || $plan->getBilling() !== PlanBilling::SUBSCRIPTION) {
                continue;
            }

            if ($plan->getPrice() <= 0) {
                continue;
            }

            $yearlyMonths = (int) round($this->prices->planPrice($plan, BillingPeriod::YEARLY) / $plan->getPrice());
            $offered = max($offered, 12 - $yearlyMonths);
        }

        return $offered;
    }

    private function periodLabel(BillingPeriod $period, int $offeredMonths): string
    {
        if ($period === BillingPeriod::YEARLY && $offeredMonths > 0) {
            return sprintf(
                Translation::translate('%s · %d mois offerts'),
                $period->label(),
                $offeredMonths
            );
        }

        return $period->label();
    }

    private function establishmentsPerk(?int $max): string
    {
        if ($max === null) {
            return Translation::translate('Établissements illimités');
        }

        if ($max > 1) {
            return sprintf(
                Translation::translate('%d établissements'),
                $max
            );
        }

        return sprintf(
            Translation::translate('%d établissement'),
            $max
        );
    }

    private function commitmentPerk(int $months): string
    {
        if ($months > 0) {
            return sprintf(
                Translation::translate('Engagement %d mois'),
                $months
            );
        }

        return Translation::translate('Sans engagement');
    }
}