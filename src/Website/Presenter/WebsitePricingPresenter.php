<?php

namespace App\Website\Presenter;

use App\Entity\Website\WebsiteConfig;
use App\Entity\Website\WebsitePlan;
use App\Entity\Website\WebsitePromoCode;
use App\Shared\Helper\DateHelper;
use App\Shared\Helper\MoneyHelper;
use DateTimeImmutable;

class WebsitePricingPresenter
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_ENDED = 'ended';

    public function present(WebsiteConfig $website, ?WebsiteConfig $live): array
    {
        $livePlans = $this->livePlans($website, $live);
        $plans = [];
        $changed = [];

        foreach ($website->getPlans() as $plan) {
            $data = $this->plan($plan, $livePlans[$plan->getCode()] ?? null);
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
            'promoCodes' => $this->promoCodes($website),
        ];
    }

    public function plan(WebsitePlan $plan, ?WebsitePlan $livePlan = null): array
    {
        return [
            'id' => $plan->getId(),
            'code' => $plan->getCode(),
            'name' => $plan->getName(),
            'description' => $plan->getDescription(),
            'billing' => $plan->getBilling()->value,
            'billingLabel' => $plan->getBilling()->label(),
            'price' => $plan->getPrice(),
            'priceLabel' => MoneyHelper::format($plan->getPrice()),
            'unitLabel' => $plan->getBilling()->unitLabel(),
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
            'livePriceLabel' => $this->livePriceLabel($livePlan),
            'changed' => $livePlan !== null && $livePlan->getPrice() !== $plan->getPrice(),
            'isNew' => $livePlan === null,
        ];
    }

    public function promoCode(WebsitePromoCode $code, ?DateTimeImmutable $now = null): array
    {
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
            'status' => $this->promoStatus($code, $now ?? new DateTimeImmutable()),
        ];
    }

    private function livePlans(WebsiteConfig $website, ?WebsiteConfig $live): array
    {
        $plans = [];

        if ($live === null || $live === $website) {
            return $plans;
        }

        foreach ($live->getPlans() as $plan) {
            $plans[$plan->getCode()] = $plan;
        }

        return $plans;
    }

    private function promoCodes(WebsiteConfig $website): array
    {
        $codes = [];

        foreach ($website->getPromoCodes() as $code) {
            $codes[] = $this->promoCode($code);
        }

        return $codes;
    }

    private function livePriceLabel(?WebsitePlan $livePlan): ?string
    {
        if ($livePlan === null) {
            return null;
        }

        return MoneyHelper::format($livePlan->getPrice());
    }

    private function promoStatus(WebsitePromoCode $code, DateTimeImmutable $now): string
    {
        if ($this->isEnded($code, $now)) {
            return self::STATUS_ENDED;
        }

        return self::STATUS_ACTIVE;
    }

    private function isEnded(WebsitePromoCode $code, DateTimeImmutable $now): bool
    {
        if (!$code->isActive()) {
            return true;
        }

        if ($code->getEndsAt() !== null && $code->getEndsAt() < $now) {
            return true;
        }

        return $code->getMaxUses() !== null && $code->getUsesCount() >= $code->getMaxUses();
    }
}