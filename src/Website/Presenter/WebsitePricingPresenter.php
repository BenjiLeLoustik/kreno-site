<?php

namespace App\Website\Presenter;

use App\Entity\Website\WebsiteConfig;
use App\Entity\Website\WebsitePlan;
use App\Entity\Website\WebsitePromoCode;
use App\Shared\Helper\DateHelper;
use App\Shared\Helper\MoneyHelper;
use App\Website\Enum\PlanBilling;
use DateTimeImmutable;

final class WebsitePricingPresenter
{
    public function present(WebsiteConfig $website, ?WebsiteConfig $live): array
    {
        $livePlans = [];

        if ($live !== null && $live !== $website) {
            foreach ($live->getPlans() as $plan) {
                $livePlans[$plan->getCode()] = $plan;
            }
        }

        $plans = [];
        $changed = [];

        foreach ($website->getPlans() as $plan) {
            $livePlan = $livePlans[$plan->getCode()] ?? null;
            $data = $this->plan($plan, $livePlan);
            $plans[] = $data;

            if ($data['changed']) {
                $changed[] = $plan->getName();
            }
        }

        return [
            'pricingName' => $website->getPricingName(),
            'priceChangePolicy' => $website->getPriceChangePolicy()->value,
            'priceChangePolicyLabel' => $website->getPriceChangePolicy()->label(),
            'priceChangeNoticeDays' => $website->getPriceChangeNoticeDays(),
            'plans' => $plans,
            'changedPlans' => $changed,
            'promoCodes' => array_map(fn (WebsitePromoCode $code): array => $this->promoCode($code), $website->getPromoCodes()->toArray()),
        ];
    }

    public function plan(WebsitePlan $plan, ?WebsitePlan $livePlan = null): array
    {
        $perBooking = $plan->getBilling() === PlanBilling::PER_BOOKING;

        return [
            'id' => $plan->getId(),
            'code' => $plan->getCode(),
            'name' => $plan->getName(),
            'description' => $plan->getDescription(),
            'billing' => $plan->getBilling()->value,
            'billingLabel' => $plan->getBilling()->label(),
            'price' => $plan->getPrice(),
            'priceLabel' => MoneyHelper::format($plan->getPrice()),
            'unitLabel' => $perBooking ? 'HT / créneau réservé' : 'HT / mois',
            'priceIncludingTax' => MoneyHelper::includingTax($plan->getPrice(), $plan->getVatRate()),
            'yearlyPrice' => $plan->getYearlyPrice(),
            'vatRate' => $plan->getVatRate(),
            'trialDays' => $plan->getTrialDays(),
            'commitmentMonths' => $plan->getCommitmentMonths(),
            'maxEstablishments' => $plan->getMaxEstablishments(),
            'commissionFree' => $plan->isCommissionFree(),
            'featured' => $plan->isFeatured(),
            'advancedStats' => $plan->isAdvancedStats(),
            'active' => $plan->isActive(),
            'position' => $plan->getPosition(),
            'livePrice' => $livePlan?->getPrice(),
            'livePriceLabel' => $livePlan !== null ? MoneyHelper::format($livePlan->getPrice()) : null,
            'changed' => $livePlan !== null && $livePlan->getPrice() !== $plan->getPrice(),
            'isNew' => $livePlan === null,
        ];
    }

    public function promoCode(WebsitePromoCode $code, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $ended = !$code->isActive()
            || ($code->getEndsAt() !== null && $code->getEndsAt() < $now)
            || ($code->getMaxUses() !== null && $code->getUsesCount() >= $code->getMaxUses());

        return [
            'id' => $code->getId(),
            'code' => $code->getCode(),
            'description' => $code->getDescription(),
            'plan' => $code->getPlan()?->getCode(),
            'discountPercent' => $code->getDiscountPercent(),
            'freeMonths' => $code->getFreeMonths(),
            'durationMonths' => $code->getDurationMonths(),
            'maxUses' => $code->getMaxUses(),
            'usesCount' => $code->getUsesCount(),
            'startsAt' => DateHelper::iso($code->getStartsAt()),
            'endsAt' => DateHelper::iso($code->getEndsAt()),
            'active' => $code->isActive(),
            'status' => $ended ? 'ended' : 'active',
        ];
    }
}