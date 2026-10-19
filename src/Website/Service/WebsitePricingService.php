<?php

namespace App\Website\Service;

use App\Entity\User\User;
use App\Entity\Website\WebsiteConfig;
use App\Entity\Website\WebsitePlan;
use App\Entity\Website\WebsitePromoCode;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Repository\Website\WebsitePlanRepository;
use App\Repository\Website\WebsitePromoCodeRepository;
use App\Shared\Helper\DateHelper;
use App\Shared\Helper\MoneyHelper;
use App\Website\Dto\PriceChangePolicyInput;
use App\Website\Dto\WebsitePlanInput;
use App\Website\Dto\WebsitePromoCodeInput;
use App\Website\Enum\PlanBilling;
use App\Website\Enum\WebsiteStatus;
use App\Website\Exception\ForeignVersionItemException;
use App\Website\Exception\PlanAlreadyExistsException;
use App\Website\Exception\PlanIdentityRequiredException;
use App\Website\Exception\PlanNotFoundException;
use App\Website\Exception\PromoCodeAlreadyExistsException;
use App\Website\Exception\PromoCodePeriodException;
use App\Website\Exception\PromoCodeRequiredException;
use App\Website\Exception\PromoCodeWithoutBenefitException;
use App\Website\Exception\ScheduledPricesLockedException;
use NeoPHP\Package\Orm\OrmManagerInterface;

class WebsitePricingService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private WebsiteVersionService $versions,
        private WebsitePlanRepository $plans,
        private WebsitePromoCodeRepository $promoCodes,
        private ActivityLogger $logger,
    ) {
    }

    public function createPlan(WebsiteConfig $website, WebsitePlanInput $input, ?User $actor = null): WebsitePlan
    {
        $this->assertPricesEditable($website);

        if ($input->code === null || $input->name === null) {
            throw new PlanIdentityRequiredException();
        }

        if ($this->plans->findOneByWebsiteAndCode($website, $input->code) !== null) {
            throw new PlanAlreadyExistsException($input->code, (string) $website->getCode());
        }

        $plan = (new WebsitePlan())
            ->setCode($input->code)
            ->setName($input->name)
            ->setPosition($input->position ?? $website->getPlans()->count())
            ->setActive(false);

        $this->apply($plan, $input);
        $website->addPlan($plan);

        $this->orm->persist($plan);
        $message = sprintf('Formule %s créée (%s)', $plan->getName(), $this->priceLabel($plan));
        $this->logger->log(ActivityType::PRICING, $message, $actor, $website, $plan);
        $this->orm->flush();

        return $plan;
    }

    public function updatePlan(
        WebsiteConfig $website,
        WebsitePlan $plan,
        WebsitePlanInput $input,
        ?User $actor = null,
    ): WebsitePlan {
        $this->assertPricesEditable($website);
        $this->assertBelongsTo($website, $plan->getWebsite());

        $before = $this->priceLabel($plan);
        $this->apply($plan, $input);

        $this->logPlanUpdate($website, $plan, $before, $actor);
        $this->orm->flush();

        return $plan;
    }

    public function createPromoCode(
        WebsiteConfig $website,
        WebsitePromoCodeInput $input,
        ?User $actor = null,
    ): WebsitePromoCode {
        $this->versions->assertEditable($website);

        if ($input->code === null) {
            throw new PromoCodeRequiredException();
        }

        if ($this->promoCodes->findOneByWebsiteAndCode($website, $input->code) !== null) {
            throw new PromoCodeAlreadyExistsException(strtoupper($input->code), (string) $website->getCode());
        }

        $promoCode = (new WebsitePromoCode())->setCode(strtoupper(trim($input->code)))->setActive(false);
        $this->applyPromoCode($website, $promoCode, $input);
        $website->addPromoCode($promoCode);

        $this->orm->persist($promoCode);
        $message = sprintf('Code promo %s créé', $promoCode->getCode());
        $this->logger->log(ActivityType::PRICING, $message, $actor, $website, $promoCode);
        $this->orm->flush();

        return $promoCode;
    }

    public function updatePromoCode(
        WebsiteConfig $website,
        WebsitePromoCode $promoCode,
        WebsitePromoCodeInput $input,
        ?User $actor = null,
    ): WebsitePromoCode {
        $this->versions->assertEditable($website);
        $this->assertBelongsTo($website, $promoCode->getWebsite());

        $this->applyPromoCode($website, $promoCode, $input);
        $message = sprintf('Code promo %s modifié', $promoCode->getCode());
        $this->logger->log(ActivityType::PRICING, $message, $actor, $website, $promoCode);
        $this->orm->flush();

        return $promoCode;
    }

    public function updatePolicy(
        WebsiteConfig $website,
        PriceChangePolicyInput $input,
        ?User $actor = null,
    ): WebsiteConfig {
        $this->assertPricesEditable($website);

        if ($input->policy !== null && $input->policy !== $website->getPriceChangePolicy()) {
            $website->setPriceChangePolicy($input->policy);
            $message = sprintf('Abonnés existants : %s', mb_strtolower($input->policy->label()));
            $this->logger->log(ActivityType::PRICING, $message, $actor, $website, $website);
        }

        if ($input->noticeDays !== null && $input->noticeDays !== $website->getPriceChangeNoticeDays()) {
            $message = sprintf(
                'Préavis de changement de prix : %d → %d jours',
                $website->getPriceChangeNoticeDays(),
                $input->noticeDays,
            );
            $this->logger->log(ActivityType::PRICING, $message, $actor, $website, $website);
            $website->setPriceChangeNoticeDays($input->noticeDays);
        }

        if ($input->pricingName !== null && trim($input->pricingName) !== $website->getPricingName()) {
            $website->setPricingName(trim($input->pricingName));
        }

        $this->orm->flush();

        return $website;
    }

    private function logPlanUpdate(WebsiteConfig $website, WebsitePlan $plan, string $before, ?User $actor): void
    {
        $after = $this->priceLabel($plan);

        if ($before === $after) {
            $message = sprintf('%s modifiée', $plan->getName());
            $this->logger->log(ActivityType::PRICING, $message, $actor, $website, $plan);

            return;
        }

        $message = sprintf('%s : %s → %s', $plan->getName(), $before, $after);
        $context = ['price' => ['from' => $before, 'to' => $after]];
        $this->logger->log(ActivityType::PRICING, $message, $actor, $website, $plan, $context);
    }

    private function apply(WebsitePlan $plan, WebsitePlanInput $input): void
    {
        if ($input->name !== null) {
            $plan->setName(trim($input->name));
        }

        if ($input->description !== null) {
            $plan->setDescription(trim($input->description) ?: null);
        }

        if ($input->billing !== null) {
            $plan->setBilling($input->billing);
        }

        if ($input->price !== null) {
            $plan->setPrice($input->price);
        }

        if ($input->yearlyPrice !== null) {
            $plan->setYearlyPrice($input->yearlyPrice ?: null);
        }

        if ($input->vatRate !== null) {
            $plan->setVatRate($input->vatRate);
        }

        if ($input->trialDays !== null) {
            $plan->setTrialDays($input->trialDays);
        }

        if ($input->commitmentMonths !== null) {
            $plan->setCommitmentMonths($input->commitmentMonths);
        }

        $this->applyEstablishments($plan, $input);
        $this->applyFlags($plan, $input);

        if ($input->position !== null) {
            $plan->setPosition($input->position);
        }

        if ($plan->getBilling() === PlanBilling::PER_BOOKING) {
            $plan->setYearlyPrice(null)->setCommissionFree(false);
        }
    }

    private function applyEstablishments(WebsitePlan $plan, WebsitePlanInput $input): void
    {
        if ($input->unlimitedEstablishments === true) {
            $plan->setMaxEstablishments(null);

            return;
        }

        if ($input->maxEstablishments !== null) {
            $plan->setMaxEstablishments($input->maxEstablishments);
        }
    }

    private function applyFlags(WebsitePlan $plan, WebsitePlanInput $input): void
    {
        if ($input->commissionFree !== null) {
            $plan->setCommissionFree($input->commissionFree);
        }

        if ($input->featured !== null) {
            $plan->setFeatured($input->featured);
        }

        if ($input->advancedStats !== null) {
            $plan->setAdvancedStats($input->advancedStats);
        }

        if ($input->active !== null) {
            $plan->setActive($input->active);
        }
    }

    private function applyPromoCode(
        WebsiteConfig $website,
        WebsitePromoCode $promoCode,
        WebsitePromoCodeInput $input,
    ): void {
        if ($input->description !== null) {
            $promoCode->setDescription(trim($input->description) ?: null);
        }

        if ($input->planCode !== null) {
            $promoCode->setPlan($this->promoPlan($website, $input->planCode));
        }

        if ($input->discountPercent !== null) {
            $promoCode->setDiscountPercent($input->discountPercent);
        }

        if ($input->freeMonths !== null) {
            $promoCode->setFreeMonths($input->freeMonths);
        }

        if ($input->durationMonths !== null) {
            $promoCode->setDurationMonths($input->durationMonths);
        }

        if ($input->maxUses !== null) {
            $promoCode->setMaxUses($input->maxUses);
        }

        $this->applyPromoPeriod($promoCode, $input);

        if ($input->active !== null) {
            $promoCode->setActive($input->active);
        }

        $this->assertPromoCodeValid($promoCode);
    }

    private function promoPlan(WebsiteConfig $website, string $planCode): ?WebsitePlan
    {
        if ($planCode === '') {
            return null;
        }

        $plan = $this->plans->findOneByWebsiteAndCode($website, $planCode);

        if ($plan === null) {
            throw new PlanNotFoundException($planCode, (string) $website->getCode());
        }

        return $plan;
    }

    private function applyPromoPeriod(WebsitePromoCode $promoCode, WebsitePromoCodeInput $input): void
    {
        if ($input->startsAt !== null) {
            $promoCode->setStartsAt(DateHelper::parse($input->startsAt));
        }

        if ($input->endsAt !== null) {
            $promoCode->setEndsAt(DateHelper::parse($input->endsAt));
        }
    }

    private function assertPromoCodeValid(WebsitePromoCode $promoCode): void
    {
        if ($promoCode->getDiscountPercent() === 0 && $promoCode->getFreeMonths() === 0) {
            throw new PromoCodeWithoutBenefitException();
        }

        $startsAt = $promoCode->getStartsAt();
        $endsAt = $promoCode->getEndsAt();

        if ($startsAt !== null && $endsAt !== null && $endsAt <= $startsAt) {
            throw new PromoCodePeriodException();
        }
    }

    private function priceLabel(WebsitePlan $plan): string
    {
        $price = MoneyHelper::format($plan->getPrice());

        if ($plan->getBilling() === PlanBilling::PER_BOOKING) {
            return $price . ' HT par réservation';
        }

        return $price . ' HT';
    }

    private function assertPricesEditable(WebsiteConfig $website): void
    {
        $this->versions->assertEditable($website);

        if ($website->getStatus() === WebsiteStatus::SCHEDULED) {
            throw new ScheduledPricesLockedException((string) $website->getCode());
        }
    }

    private function assertBelongsTo(WebsiteConfig $website, ?WebsiteConfig $owner): void
    {
        if ($owner !== $website) {
            throw new ForeignVersionItemException((string) $website->getCode());
        }
    }
}