<?php

namespace App\Billing\Service;

use App\Billing\Enum\BillingPeriod;
use App\Billing\Enum\SubscriptionStatus;
use App\Billing\Helper\PeriodHelper;
use App\Entity\Company\CompanySubscription;
use App\Entity\Website\WebsitePlan;
use App\Entity\Website\WebsitePromoCode;
use App\Shared\Helper\MoneyHelper;
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

    public function discounted(int $price, ?WebsitePromoCode $promo, int $monthIndex, BillingPeriod $period = BillingPeriod::MONTHLY): int
    {
        if ($promo === null) {
            return $price;
        }

        if ($period === BillingPeriod::YEARLY) {
            $monthly = $price / 12;
            $total = 0.0;

            for ($month = 0; $month < 12; ++$month) {
                $total += $this->discounted((int) round($monthly), $promo, $month);
            }

            return (int) round($total);
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

        $diff = $endsAt->modify(sprintf('-%d months', max($promo->getDurationMonths(), $promo->getFreeMonths())))->diff($periodStart);

        return $this->discounted($subscription->getUnitPrice(), $promo, max(0, $diff->y * 12 + $diff->m), $subscription->getPeriod());
    }

    public function quote(WebsitePlan $plan, BillingPeriod $period, ?WebsitePromoCode $promo = null): array
    {
        $vatRate = $plan->getVatRate();
        $price = $this->planPrice($plan, $period);
        $first = $this->discounted($price, $promo, 0, $period);
        $perBooking = $plan->getBilling() === PlanBilling::PER_BOOKING;

        return [
            'plan' => $plan->getCode(),
            'period' => $period->value,
            'perBooking' => $perBooking,
            'price' => $price,
            'priceLabel' => MoneyHelper::formatExact($price) . ' HT',
            'priceIncludingTax' => MoneyHelper::includingTax($price, $vatRate),
            'priceIncludingTaxLabel' => MoneyHelper::formatExact(MoneyHelper::includingTax($price, $vatRate)) . ' TTC',
            'unit' => $perBooking ? 'par créneau réservé' : ($period === BillingPeriod::YEARLY ? 'par an' : 'par mois'),
            'monthlyEquivalent' => $period === BillingPeriod::YEARLY && !$perBooking ? MoneyHelper::formatExact((int) round($price / 12)) . ' / mois' : null,
            'vatRate' => $vatRate,
            'first' => $first,
            'firstIncludingTax' => MoneyHelper::includingTax($first, $vatRate),
            'firstLabel' => $perBooking
                ? 'Facturé en fin de mois selon les créneaux réservés'
                : ($promo !== null && $first !== $price
                    ? sprintf('%s TTC (puis %s dès le %de mois)', MoneyHelper::formatExact(MoneyHelper::includingTax($first, $vatRate)), MoneyHelper::formatExact(MoneyHelper::includingTax($price, $vatRate)), max($promo->getDurationMonths(), $promo->getFreeMonths()) + 1)
                    : MoneyHelper::formatExact(MoneyHelper::includingTax($price, $vatRate)) . ' TTC'),
            'trialDays' => $plan->getTrialDays(),
        ];
    }

    public function prorata(CompanySubscription $current, WebsitePlan $plan, BillingPeriod $period, ?WebsitePromoCode $promo, DateTimeImmutable $now): array
    {
        $start = $current->getCurrentPeriodStart() ?? $now;
        $end = $current->getCurrentPeriodEnd() ?? $now;
        $ratio = PeriodHelper::remainingRatio($start, $end, $now);
        $currentIsSubscription = $current->getPlan()?->getBilling() === PlanBilling::SUBSCRIPTION && $current->getStatus() !== SubscriptionStatus::PAUSED;
        $credit = $currentIsSubscription ? (int) round($this->amountFor($current, $start) * $ratio) : 0;
        $samePeriod = $currentIsSubscription && $current->getPeriod() === $period;
        $newPrice = $this->discounted($this->planPrice($plan, $period), $promo, 0, $period);
        $charge = $samePeriod ? (int) round($newPrice * $ratio) : $newPrice;
        $due = max(0, $charge - $credit);
        $vatRate = $plan->getVatRate();

        return [
            'keepsPeriodEnd' => $samePeriod,
            'periodStart' => $samePeriod ? $start : $now,
            'periodEnd' => $samePeriod ? $end : PeriodHelper::end($now, $period),
            'charge' => $charge,
            'credit' => $credit,
            'due' => $due,
            'chargeLabel' => MoneyHelper::formatExact(MoneyHelper::includingTax($charge, $vatRate)),
            'creditLabel' => '-' . MoneyHelper::formatExact(MoneyHelper::includingTax($credit, $vatRate)),
            'dueIncludingTax' => MoneyHelper::includingTax($due, $vatRate),
            'dueLabel' => MoneyHelper::formatExact(MoneyHelper::includingTax($due, $vatRate)),
        ];
    }
}