<?php

namespace App\Billing\Service;

use App\Billing\Enum\BillingPeriod;
use App\Billing\Enum\SubscriptionStatus;
use App\Billing\Helper\PeriodHelper;
use App\Entity\Company\CompanySubscription;
use App\Entity\Website\WebsitePlan;
use App\Entity\Website\WebsitePromoCode;
use App\Shared\Helper\MoneyHelper;
use App\Shared\Helper\Translation;
use App\Website\Enum\PlanBilling;
use DateTimeImmutable;

class PriceCalculator
{
    public const YEARLY_MONTHS_CHARGED = 10;

    public function planPrice(WebsitePlan $plan, BillingPeriod $period): int
    {
        if ($plan->getBilling() === PlanBilling::PER_BOOKING || $period === BillingPeriod::MONTHLY) {
            return $plan->getPrice();
        }

        return $plan->getYearlyPrice() ?? $plan->getPrice() * self::YEARLY_MONTHS_CHARGED;
    }

    public function discounted(
        int $price,
        ?WebsitePromoCode $promo,
        int $monthIndex,
        BillingPeriod $period = BillingPeriod::MONTHLY,
    ): int {
        if ($promo === null) {
            return $price;
        }

        if ($period === BillingPeriod::YEARLY) {
            return $this->discountedYear($price, $promo);
        }

        if ($monthIndex < $promo->getFreeMonths()) {
            return 0;
        }

        if ($monthIndex < $promo->getDurationMonths()) {
            return MoneyHelper::applyDiscount($price, $promo->getDiscountPercent());
        }

        return $price;
    }

    public function amountFor(CompanySubscription $subscription, DateTimeImmutable $periodStart): int
    {
        $promo = $subscription->getPromoCode();
        $endsAt = $subscription->getPromoEndsAt();

        if ($promo === null || $endsAt === null || $periodStart >= $endsAt) {
            return $subscription->getUnitPrice();
        }

        $promoStart = $endsAt->modify(sprintf('-%d months', $this->promoMonths($promo)));
        $diff = $promoStart->diff($periodStart);
        $monthIndex = max(0, $diff->y * 12 + $diff->m);

        return $this->discounted($subscription->getUnitPrice(), $promo, $monthIndex, $subscription->getPeriod());
    }

    public function quote(WebsitePlan $plan, BillingPeriod $period, ?WebsitePromoCode $promo = null): array
    {
        $vatRate = $plan->getVatRate();
        $price = $this->planPrice($plan, $period);
        $priceIncludingTax = MoneyHelper::includingTax($price, $vatRate);
        $first = $this->discounted($price, $promo, 0, $period);

        return [
            'plan' => $plan->getCode(),
            'period' => $period->value,
            'perBooking' => $this->isPerBooking($plan),
            'price' => $price,
            'priceLabel' => MoneyHelper::formatExact($price) . Translation::translate(' HT'),
            'priceIncludingTax' => $priceIncludingTax,
            'priceIncludingTaxLabel' => MoneyHelper::formatExact($priceIncludingTax) . Translation::translate(' TTC'),
            'unit' => $this->unit($plan, $period),
            'monthlyEquivalent' => $this->monthlyEquivalent($plan, $period, $price),
            'vatRate' => $vatRate,
            'first' => $first,
            'firstIncludingTax' => MoneyHelper::includingTax($first, $vatRate),
            'firstLabel' => $this->firstLabel($plan, $promo, $price, $first),
            'trialDays' => $plan->getTrialDays(),
        ];
    }

    public function prorata(
        CompanySubscription $current,
        WebsitePlan $plan,
        BillingPeriod $period,
        ?WebsitePromoCode $promo,
        DateTimeImmutable $now,
    ): array {
        $start = $current->getCurrentPeriodStart() ?? $now;
        $end = $current->getCurrentPeriodEnd() ?? $now;
        $ratio = PeriodHelper::remainingRatio($start, $end, $now);
        $isSubscription = $this->isRunningSubscription($current);
        $samePeriod = $isSubscription && $current->getPeriod() === $period;
        $charge = $this->discounted($this->planPrice($plan, $period), $promo, 0, $period);
        $credit = 0;
        $periodStart = $now;
        $periodEnd = PeriodHelper::end($now, $period);

        if ($isSubscription) {
            $credit = (int) round($this->amountFor($current, $start) * $ratio);
        }

        if ($samePeriod) {
            $charge = (int) round($charge * $ratio);
            $periodStart = $start;
            $periodEnd = $end;
        }

        $due = max(0, $charge - $credit);
        $vatRate = $plan->getVatRate();

        return [
            'keepsPeriodEnd' => $samePeriod,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'charge' => $charge,
            'credit' => $credit,
            'due' => $due,
            'chargeLabel' => MoneyHelper::formatExact(MoneyHelper::includingTax($charge, $vatRate)),
            'creditLabel' => '-' . MoneyHelper::formatExact(MoneyHelper::includingTax($credit, $vatRate)),
            'dueIncludingTax' => MoneyHelper::includingTax($due, $vatRate),
            'dueLabel' => MoneyHelper::formatExact(MoneyHelper::includingTax($due, $vatRate)),
        ];
    }

    private function discountedYear(int $price, WebsitePromoCode $promo): int
    {
        $monthly = (int) round($price / 12);
        $total = 0;

        for ($month = 0; $month < 12; ++$month) {
            $total += $this->discounted($monthly, $promo, $month);
        }

        return $total;
    }

    private function promoMonths(WebsitePromoCode $promo): int
    {
        return max($promo->getDurationMonths(), $promo->getFreeMonths());
    }

    private function isPerBooking(WebsitePlan $plan): bool
    {
        return $plan->getBilling() === PlanBilling::PER_BOOKING;
    }

    private function isRunningSubscription(CompanySubscription $subscription): bool
    {
        if ($subscription->getPlan()?->getBilling() !== PlanBilling::SUBSCRIPTION) {
            return false;
        }

        return $subscription->getStatus() !== SubscriptionStatus::PAUSED;
    }

    private function unit(WebsitePlan $plan, BillingPeriod $period): string
    {
        if ($this->isPerBooking($plan)) {
            return Translation::translate('par créneau réservé');
        }

        if ($period === BillingPeriod::YEARLY) {
            return Translation::translate('par an');
        }

        return Translation::translate('par mois');
    }

    private function monthlyEquivalent(WebsitePlan $plan, BillingPeriod $period, int $price): ?string
    {
        if ($period !== BillingPeriod::YEARLY || $this->isPerBooking($plan)) {
            return null;
        }

        return MoneyHelper::formatExact((int) round($price / 12)) . Translation::translate(' / mois');
    }

    private function firstLabel(WebsitePlan $plan, ?WebsitePromoCode $promo, int $price, int $first): string
    {
        if ($this->isPerBooking($plan)) {
            return Translation::translate('Facturé en fin de mois selon les créneaux réservés');
        }

        $vatRate = $plan->getVatRate();
        $priceLabel = MoneyHelper::formatExact(MoneyHelper::includingTax($price, $vatRate));

        if ($promo === null || $first === $price) {
            return $priceLabel . Translation::translate(' TTC');
        }

        $firstLabel = MoneyHelper::formatExact(MoneyHelper::includingTax($first, $vatRate));

        return sprintf(
            Translation::translate('%s TTC (puis %s dès le %de mois)'),
            $firstLabel,
            $priceLabel,
            $this->promoMonths($promo) + 1
        );
    }
}