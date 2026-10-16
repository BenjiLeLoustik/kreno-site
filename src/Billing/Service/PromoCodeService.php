<?php

namespace App\Billing\Service;

use App\Entity\Website\WebsitePlan;
use App\Entity\Website\WebsitePromoCode;
use App\Repository\Website\WebsitePromoCodeRepository;
use App\Shared\Exception\BusinessRuleException;
use App\Website\Provider\WebsiteProvider;
use App\Website\Enum\PlanBilling;
use DateTimeImmutable;
use NeoPHP\Package\Orm\OrmManagerInterface;

class PromoCodeService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private WebsiteProvider $website,
        private WebsitePromoCodeRepository $promoCodes,
    ) {
    }

    public function find(?string $code): ?WebsitePromoCode
    {
        $code = mb_strtoupper(trim((string) $code));

        return $code !== '' ? $this->promoCodes->findOneByWebsiteAndCode($this->website->getLive(), $code) : null;
    }

    public function resolve(?string $code, WebsitePlan $plan, ?DateTimeImmutable $now = null): ?WebsitePromoCode
    {
        if ($code === null || trim($code) === '') {
            return null;
        }

        $promo = $this->find($code);

        if ($promo === null || !$this->isUsable($promo, $plan, $now)) {
            throw new BusinessRuleException('Ce code n\'existe pas ou a expiré.');
        }

        return $promo;
    }

    public function isUsable(WebsitePromoCode $promo, WebsitePlan $plan, ?DateTimeImmutable $now = null): bool
    {
        $now ??= new DateTimeImmutable();

        return $promo->isActive()
            && $plan->getBilling() === PlanBilling::SUBSCRIPTION
            && ($promo->getPlan() === null || $promo->getPlan()->getCode() === $plan->getCode())
            && ($promo->getStartsAt() === null || $promo->getStartsAt() <= $now)
            && ($promo->getEndsAt() === null || $promo->getEndsAt() > $now)
            && ($promo->getMaxUses() === null || $promo->getUsesCount() < $promo->getMaxUses());
    }

    public function consume(WebsitePromoCode $promo): void
    {
        $promo->setUsesCount($promo->getUsesCount() + 1);
        $this->orm->flush();
    }

    public function months(WebsitePromoCode $promo): int
    {
        return max($promo->getDurationMonths(), $promo->getFreeMonths());
    }

    public function describe(WebsitePromoCode $promo): string
    {
        $parts = [];

        if ($promo->getFreeMonths() > 0) {
            $parts[] = sprintf('%d mois offert%s', $promo->getFreeMonths(), $promo->getFreeMonths() > 1 ? 's' : '');
        }

        if ($promo->getDiscountPercent() > 0) {
            $parts[] = sprintf('-%d %% pendant %d mois', $promo->getDiscountPercent(), $promo->getDurationMonths());
        }

        return implode(', puis ', $parts);
    }
}