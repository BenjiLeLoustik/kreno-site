<?php

namespace App\Billing\Service;

use App\Billing\Exception\InvalidPromoCodeException;
use App\Entity\Website\WebsitePlan;
use App\Entity\Website\WebsitePromoCode;
use App\Repository\Website\WebsitePromoCodeRepository;
use App\Shared\Helper\Translation;
use App\Website\Enum\PlanBilling;
use App\Website\Provider\WebsiteProvider;
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

        if ($code === '') {
            return null;
        }

        return $this->promoCodes->findOneByWebsiteAndCode($this->website->getLive(), $code);
    }

    public function resolve(?string $code, WebsitePlan $plan, ?DateTimeImmutable $now = null): ?WebsitePromoCode
    {
        if ($code === null || trim($code) === '') {
            return null;
        }

        $promo = $this->find($code);

        if ($promo === null || !$this->isUsable($promo, $plan, $now)) {
            throw new InvalidPromoCodeException();
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
            $parts[] = $this->freeMonths($promo->getFreeMonths());
        }

        if ($promo->getDiscountPercent() > 0) {
            $parts[] = sprintf(
                Translation::translate('-%d %% pendant %d mois'),
                $promo->getDiscountPercent(),
                $promo->getDurationMonths()
            );
        }

        return array_reduce(
            array_slice($parts, 1),
            static fn (string $previous, string $next): string => Translation::translate(
                '{previous}, puis {next}',
                [
                    'previous' => $previous,
                    'next' => $next
                ],
            ),
            $parts[0] ?? '',
        );
    }

    private function freeMonths(int $months): string
    {
        if ($months > 1) {
            return sprintf(Translation::translate('%d mois offerts'), $months);
        }

        return sprintf(Translation::translate('%d mois offert'), $months);
    }
}