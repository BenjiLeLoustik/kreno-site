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
use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\DateHelper;
use App\Shared\Helper\MoneyHelper;
use App\Website\Dto\PriceChangePolicyInput;
use App\Website\Dto\WebsitePlanInput;
use App\Website\Dto\WebsitePromoCodeInput;
use App\Website\Enum\PlanBilling;
use App\Website\Enum\WebsiteStatus;
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
            throw new BusinessRuleException('Le code et le nom de la formule sont obligatoires.');
        }

        if ($this->plans->findOneByWebsiteAndCode($website, $input->code) !== null) {
            throw new BusinessRuleException('La formule « {code} » existe déjà dans la {version}.', ['code' => $input->code, 'version' => $website->getCode()]);
        }

        $plan = (new WebsitePlan())
            ->setCode($input->code)
            ->setName($input->name)
            ->setPosition($input->position ?? $website->getPlans()->count())
            ->setActive(false);

        $this->apply($plan, $input);
        $website->addPlan($plan);

        $this->orm->persist($plan);
        $this->logger->log(ActivityType::PRICING, sprintf('Formule %s créée (%s)', $plan->getName(), $this->priceLabel($plan)), $actor, $website, $plan);
        $this->orm->flush();

        return $plan;
    }

    public function updatePlan(WebsiteConfig $website, WebsitePlan $plan, WebsitePlanInput $input, ?User $actor = null): WebsitePlan
    {
        $this->assertPricesEditable($website);
        $this->assertBelongsTo($website, $plan->getWebsite());

        $before = $this->priceLabel($plan);
        $this->apply($plan, $input);
        $after = $this->priceLabel($plan);

        if ($before !== $after) {
            $this->logger->log(ActivityType::PRICING, sprintf('%s : %s → %s', $plan->getName(), $before, $after), $actor, $website, $plan, ['price' => ['from' => $before, 'to' => $after]]);
        } else {
            $this->logger->log(ActivityType::PRICING, sprintf('%s modifiée', $plan->getName()), $actor, $website, $plan);
        }

        $this->orm->flush();

        return $plan;
    }

    public function createPromoCode(WebsiteConfig $website, WebsitePromoCodeInput $input, ?User $actor = null): WebsitePromoCode
    {
        $this->versions->assertEditable($website);

        if ($input->code === null) {
            throw new BusinessRuleException('Le code promo est obligatoire.');
        }

        if ($this->promoCodes->findOneByWebsiteAndCode($website, $input->code) !== null) {
            throw new BusinessRuleException('Le code {code} existe déjà dans la {version}.', ['code' => strtoupper($input->code), 'version' => $website->getCode()]);
        }

        $promoCode = (new WebsitePromoCode())->setCode(strtoupper(trim($input->code)))->setActive(false);
        $this->applyPromoCode($website, $promoCode, $input);
        $website->addPromoCode($promoCode);

        $this->orm->persist($promoCode);
        $this->logger->log(ActivityType::PRICING, sprintf('Code promo %s créé', $promoCode->getCode()), $actor, $website, $promoCode);
        $this->orm->flush();

        return $promoCode;
    }

    public function updatePromoCode(WebsiteConfig $website, WebsitePromoCode $promoCode, WebsitePromoCodeInput $input, ?User $actor = null): WebsitePromoCode
    {
        $this->versions->assertEditable($website);
        $this->assertBelongsTo($website, $promoCode->getWebsite());

        $this->applyPromoCode($website, $promoCode, $input);
        $this->logger->log(ActivityType::PRICING, sprintf('Code promo %s modifié', $promoCode->getCode()), $actor, $website, $promoCode);
        $this->orm->flush();

        return $promoCode;
    }

    public function updatePolicy(WebsiteConfig $website, PriceChangePolicyInput $input, ?User $actor = null): WebsiteConfig
    {
        $this->assertPricesEditable($website);

        if ($input->policy !== null && $input->policy !== $website->getPriceChangePolicy()) {
            $website->setPriceChangePolicy($input->policy);
            $this->logger->log(ActivityType::PRICING, sprintf('Abonnés existants : %s', mb_strtolower($input->policy->label())), $actor, $website, $website);
        }

        if ($input->noticeDays !== null && $input->noticeDays !== $website->getPriceChangeNoticeDays()) {
            $this->logger->log(ActivityType::PRICING, sprintf('Préavis de changement de prix : %d → %d jours', $website->getPriceChangeNoticeDays(), $input->noticeDays), $actor, $website, $website);
            $website->setPriceChangeNoticeDays($input->noticeDays);
        }

        if ($input->pricingName !== null && trim($input->pricingName) !== $website->getPricingName()) {
            $website->setPricingName(trim($input->pricingName));
        }

        $this->orm->flush();

        return $website;
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

        if ($input->unlimitedEstablishments === true) {
            $plan->setMaxEstablishments(null);
        } elseif ($input->maxEstablishments !== null) {
            $plan->setMaxEstablishments($input->maxEstablishments);
        }

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

        if ($input->position !== null) {
            $plan->setPosition($input->position);
        }

        if ($plan->getBilling() === PlanBilling::PER_BOOKING) {
            $plan->setYearlyPrice(null)->setCommissionFree(false);
        }
    }

    private function applyPromoCode(WebsiteConfig $website, WebsitePromoCode $promoCode, WebsitePromoCodeInput $input): void
    {
        if ($input->description !== null) {
            $promoCode->setDescription(trim($input->description) ?: null);
        }

        if ($input->planCode !== null) {
            $plan = $input->planCode === '' ? null : $this->plans->findOneByWebsiteAndCode($website, $input->planCode);

            if ($input->planCode !== '' && $plan === null) {
                throw new BusinessRuleException('La formule « {code} » n\'existe pas dans la {version}.', ['code' => $input->planCode, 'version' => $website->getCode()]);
            }

            $promoCode->setPlan($plan);
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

        if ($input->startsAt !== null) {
            $promoCode->setStartsAt(DateHelper::parse($input->startsAt));
        }

        if ($input->endsAt !== null) {
            $promoCode->setEndsAt(DateHelper::parse($input->endsAt));
        }

        if ($input->active !== null) {
            $promoCode->setActive($input->active);
        }

        if ($promoCode->getDiscountPercent() === 0 && $promoCode->getFreeMonths() === 0) {
            throw new BusinessRuleException('Un code promo doit donner une remise ou des mois offerts.');
        }

        if ($promoCode->getStartsAt() !== null && $promoCode->getEndsAt() !== null && $promoCode->getEndsAt() <= $promoCode->getStartsAt()) {
            throw new BusinessRuleException('La fin de validité du code doit être après son début.');
        }
    }

    private function priceLabel(WebsitePlan $plan): string
    {
        return MoneyHelper::format($plan->getPrice()) . ($plan->getBilling() === PlanBilling::PER_BOOKING ? ' HT par réservation' : ' HT');
    }

    private function assertPricesEditable(WebsiteConfig $website): void
    {
        $this->versions->assertEditable($website);

        if ($website->getStatus() === WebsiteStatus::SCHEDULED) {
            throw new BusinessRuleException('La {code} est planifiée et ses changements de prix ont déjà été annoncés aux abonnés. Annulez la planification pour modifier les formules, puis replanifiez-la : seules les différences seront envoyées.', ['code' => $website->getCode()]);
        }
    }

    private function assertBelongsTo(WebsiteConfig $website, ?WebsiteConfig $owner): void
    {
        if ($owner !== $website) {
            throw new BusinessRuleException('Cet élément n\'appartient pas à la {version}.', ['version' => $website->getCode()]);
        }
    }
}