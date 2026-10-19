<?php

namespace App\Website\Service;

use App\Billing\Service\PriceChangeNotifier;
use App\Entity\User\User;
use App\Entity\Website\WebsiteConfig;
use App\Entity\Website\WebsitePlan;
use App\Entity\Website\WebsitePromoCode;
use App\Entity\Website\WebsiteRule;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Repository\Company\CompanySubscriptionRepository;
use App\Repository\Website\WebsiteConfigRepository;
use App\Website\Dto\WebsiteIdentityInput;
use App\Website\Enum\IdentityField;
use App\Website\Enum\WebsiteStatus;
use App\Website\Event\WebsiteVersionPublishedEvent;
use App\Website\Event\WebsiteVersionScheduledEvent;
use App\Website\Exception\MissingVersionRulesException;
use App\Website\Exception\NoActiveVersionPlanException;
use App\Website\Exception\PastScheduleDateException;
use App\Website\Exception\PlanInUseException;
use App\Website\Exception\ScheduledVersionBlocksRestoreException;
use App\Website\Exception\VersionAlreadyScheduledException;
use App\Website\Exception\VersionNotArchivedException;
use App\Website\Exception\VersionNotDeletableException;
use App\Website\Exception\VersionNotScheduledException;
use App\Website\Exception\WebsiteNotEditableException;
use App\Website\Helper\WebsiteVersionHelper;
use App\Website\Provider\WebsiteProvider;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class WebsiteVersionService
{
    public const COPY_NAME = 'Copie de %s';

    public const COPY_DESCRIPTION = 'Créée à partir de la %s';

    public const RESTORE_NAME = 'Retour à la %s';

    public const RESTORE_DESCRIPTION = 'Restauration de la %s';

    public function __construct(
        private OrmManagerInterface $orm,
        private WebsiteConfigRepository $websites,
        private WebsiteProvider $provider,
        private ActivityLogger $logger,
        private EventManagerInterface $events,
        private PriceChangeNotifier $priceChanges,
        private CompanySubscriptionRepository $subscriptions,
    ) {
    }

    public function duplicate(WebsiteConfig $source, ?User $actor = null): WebsiteConfig
    {
        $copy = $this->copyConfig($source, $actor);

        if ($source->getRule() !== null) {
            $copy->setRule($this->copyRule($source->getRule()));
        }

        $plans = [];

        foreach ($source->getPlans() as $plan) {
            $plans[$plan->getCode()] = $this->copyPlan($plan);
            $copy->addPlan($plans[$plan->getCode()]);
        }

        foreach ($source->getPromoCodes() as $promoCode) {
            $copy->addPromoCode($this->copyPromoCode($promoCode, $plans));
        }

        $this->orm->persist($copy);
        $message = sprintf('Brouillon créé à partir de la %s', $source->getCode());
        $this->logger->log(ActivityType::VERSION, $message, $actor, $copy, $copy);
        $this->orm->flush();

        return $copy;
    }

    public function updateIdentity(
        WebsiteConfig $website,
        WebsiteIdentityInput $input,
        ?User $actor = null,
    ): WebsiteConfig {
        $this->assertEditable($website);

        $changed = false;

        foreach (IdentityField::cases() as $field) {
            if ($this->updateIdentityField($website, $input, $field, $actor)) {
                $changed = true;
            }
        }

        if ($changed) {
            $this->orm->flush();
        }

        return $website;
    }

    public function schedule(
        WebsiteConfig $website,
        DateTimeImmutable $scheduledAt,
        ?User $actor = null,
    ): WebsiteConfig {
        $this->assertEditable($website);

        if ($scheduledAt <= new DateTimeImmutable()) {
            throw new PastScheduleDateException();
        }

        $alreadyScheduled = $this->websites->findScheduled();

        if ($alreadyScheduled !== null && $alreadyScheduled !== $website) {
            throw new VersionAlreadyScheduledException((string) $alreadyScheduled->getCode());
        }

        $this->assertPublishable($website);
        $this->priceChanges->assertNotice($website, $scheduledAt);

        $website->setStatus(WebsiteStatus::SCHEDULED)->setScheduledAt($scheduledAt);
        $message = sprintf('Mise en ligne planifiée le %s', $scheduledAt->format('d/m/Y à H:i'));
        $this->logger->log(ActivityType::VERSION, $message, $actor, $website, $website);
        $this->orm->flush();

        $this->events->dispatch(new WebsiteVersionScheduledEvent($website));

        return $website;
    }

    public function unschedule(WebsiteConfig $website, ?User $actor = null): WebsiteConfig
    {
        if ($website->getStatus() !== WebsiteStatus::SCHEDULED) {
            throw new VersionNotScheduledException((string) $website->getCode());
        }

        $website->setStatus(WebsiteStatus::DRAFT)->setScheduledAt(null);
        $this->logger->log(ActivityType::VERSION, 'Planification annulée', $actor, $website, $website);
        $this->orm->flush();

        return $website;
    }

    public function publish(WebsiteConfig $website, ?User $actor = null): WebsiteConfig
    {
        $this->assertEditable($website);
        $this->assertPublishable($website);

        $now = new DateTimeImmutable();
        $wasScheduled = $website->getStatus() === WebsiteStatus::SCHEDULED;

        if ($this->needsNoticeCheck($website, $wasScheduled, $now)) {
            $this->priceChanges->assertNotice($website, $now, $now);
        }

        $previous = $this->websites->findLive();

        if ($previous === $website) {
            $previous = null;
        }

        $this->orm->transactional(function () use ($website, $actor, $now, $previous): void {
            $previous?->setStatus(WebsiteStatus::ARCHIVED)->setArchivedAt($now);
            $website->setStatus(WebsiteStatus::LIVE)->setPublishedAt($now)->setScheduledAt(null);

            $this->logger->log(ActivityType::VERSION, $this->publishMessage($previous), $actor, $website, $website);
            $this->orm->flush();
        });

        $this->provider->reset();
        $this->events->dispatch(new WebsiteVersionPublishedEvent($website, $previous, $wasScheduled));

        return $website;
    }

    public function restore(WebsiteConfig $source, ?User $actor = null): WebsiteConfig
    {
        if ($source->getStatus() !== WebsiteStatus::ARCHIVED) {
            throw new VersionNotArchivedException((string) $source->getCode(), $this->statusLabel($source));
        }

        $scheduled = $this->websites->findScheduled();

        if ($scheduled !== null) {
            throw new ScheduledVersionBlocksRestoreException((string) $scheduled->getCode());
        }

        $copy = $this->duplicate($source, $actor);
        $copy
            ->setName(sprintf(self::RESTORE_NAME, $source->getCode()))
            ->setDescription(sprintf(self::RESTORE_DESCRIPTION, $source->getCode()));
        $this->orm->flush();

        return $this->publish($copy, $actor);
    }

    public function delete(WebsiteConfig $website, ?User $actor = null): void
    {
        if (!$website->getStatus()->isDeletable()) {
            throw new VersionNotDeletableException((string) $website->getCode(), $this->statusLabel($website));
        }

        foreach ($website->getPlans() as $plan) {
            if ($this->subscriptions->count(['plan' => $plan]) > 0) {
                throw new PlanInUseException((string) $website->getCode(), (string) $plan->getName());
            }
        }

        $code = $website->getCode();

        $this->orm->transactional(function () use ($website, $actor, $code): void {
            $this->orm->remove($website);
            $this->logger->log(ActivityType::VERSION, sprintf('Version %s supprimée', $code), $actor);
            $this->orm->flush();
        });
    }

    public function publishDue(DateTimeImmutable $now): array
    {
        $published = [];

        foreach ($this->websites->findDueForPublication($now) as $website) {
            $published[] = $this->publish($website);
        }

        return $published;
    }

    public function assertEditable(WebsiteConfig $website): void
    {
        if (!WebsiteVersionHelper::isEditable($website)) {
            throw new WebsiteNotEditableException($website);
        }
    }

    private function updateIdentityField(
        WebsiteConfig $website,
        WebsiteIdentityInput $input,
        IdentityField $field,
        ?User $actor,
    ): bool {
        $value = $input->{$field->value};

        if ($value === null) {
            return false;
        }

        $value = trim($value);
        $current = $website->{$field->getter()}();

        if ($value === (string) $current) {
            return false;
        }

        $website->{$field->setter()}($value);

        $change = [
            'from' => $current,
            'to' => $value
        ];
        $message = sprintf('%s : %s → %s', $field->label(), $current ?? '—', $value);
        $this->logger->log(ActivityType::IDENTITY, $message, $actor, $website, $website, [$field->value => $change]);

        return true;
    }

    private function needsNoticeCheck(WebsiteConfig $website, bool $wasScheduled, DateTimeImmutable $now): bool
    {
        if (!$wasScheduled || $website->getScheduledAt() === null) {
            return true;
        }

        return $website->getScheduledAt() > $now;
    }

    private function publishMessage(?WebsiteConfig $previous): string
    {
        if ($previous === null) {
            return 'Mise en ligne';
        }

        return sprintf('Mise en ligne, la %s est archivée', $previous->getCode());
    }

    private function statusLabel(WebsiteConfig $website): string
    {
        return mb_strtolower($website->getStatus()->label());
    }

    private function assertPublishable(WebsiteConfig $website): void
    {
        if ($website->getRule() === null) {
            throw new MissingVersionRulesException((string) $website->getCode());
        }

        foreach ($website->getPlans() as $plan) {
            if ($plan->isActive()) {
                return;
            }
        }

        throw new NoActiveVersionPlanException((string) $website->getCode());
    }

    private function copyConfig(WebsiteConfig $source, ?User $actor): WebsiteConfig
    {
        return (new WebsiteConfig())
            ->setCode(WebsiteVersionHelper::nextCode($this->websites->findAllCodes()))
            ->setName(sprintf(self::COPY_NAME, $source->getCode()))
            ->setDescription(sprintf(self::COPY_DESCRIPTION, $source->getCode()))
            ->setSiteName((string) $source->getSiteName())
            ->setDomain((string) $source->getDomain())
            ->setTagline((string) $source->getTagline())
            ->setSiteDescription((string) $source->getSiteDescription())
            ->setSeoTitle($source->getSeoTitle())
            ->setContactEmail((string) $source->getContactEmail())
            ->setPricingName((string) $source->getPricingName())
            ->setPriceChangePolicy($source->getPriceChangePolicy())
            ->setPriceChangeNoticeDays($source->getPriceChangeNoticeDays())
            ->setStatus(WebsiteStatus::DRAFT)
            ->setParent($source)
            ->setCreatedBy($actor);
    }

    private function copyRule(WebsiteRule $rule): WebsiteRule
    {
        return (new WebsiteRule())
            ->setFreeCancellationMinutes($rule->getFreeCancellationMinutes())
            ->setBookingLeadMinutes($rule->getBookingLeadMinutes())
            ->setMaxUpcomingBookingsPerUser($rule->getMaxUpcomingBookingsPerUser())
            ->setToleratedNoShows($rule->getToleratedNoShows())
            ->setNoShowWindowDays($rule->getNoShowWindowDays())
            ->setSuspensionDays($rule->getSuspensionDays())
            ->setMinDiscount($rule->getMinDiscount())
            ->setMaxDiscount($rule->getMaxDiscount())
            ->setDefaultDiscount($rule->getDefaultDiscount())
            ->setPublicationWindowDays($rule->getPublicationWindowDays())
            ->setAutoRemovalMinutes($rule->getAutoRemovalMinutes());
    }

    private function copyPlan(WebsitePlan $plan): WebsitePlan
    {
        return (new WebsitePlan())
            ->setCode((string) $plan->getCode())
            ->setName((string) $plan->getName())
            ->setDescription($plan->getDescription())
            ->setBilling($plan->getBilling())
            ->setPrice($plan->getPrice())
            ->setYearlyPrice($plan->getYearlyPrice())
            ->setVatRate($plan->getVatRate())
            ->setTrialDays($plan->getTrialDays())
            ->setCommitmentMonths($plan->getCommitmentMonths())
            ->setMaxEstablishments($plan->getMaxEstablishments())
            ->setCommissionFree($plan->isCommissionFree())
            ->setFeatured($plan->isFeatured())
            ->setAdvancedStats($plan->isAdvancedStats())
            ->setActive($plan->isActive())
            ->setPosition($plan->getPosition());
    }

    private function copyPromoCode(WebsitePromoCode $promoCode, array $plans): WebsitePromoCode
    {
        return (new WebsitePromoCode())
            ->setCode((string) $promoCode->getCode())
            ->setDescription($promoCode->getDescription())
            ->setPlan($this->copiedPlan($promoCode->getPlan(), $plans))
            ->setDiscountPercent($promoCode->getDiscountPercent())
            ->setFreeMonths($promoCode->getFreeMonths())
            ->setDurationMonths($promoCode->getDurationMonths())
            ->setMaxUses($promoCode->getMaxUses())
            ->setStartsAt($promoCode->getStartsAt())
            ->setEndsAt($promoCode->getEndsAt())
            ->setActive($promoCode->isActive());
    }

    private function copiedPlan(?WebsitePlan $plan, array $plans): ?WebsitePlan
    {
        if ($plan === null) {
            return null;
        }

        return $plans[$plan->getCode()] ?? null;
    }
}