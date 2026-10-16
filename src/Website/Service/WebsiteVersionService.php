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
use App\Shared\Exception\BusinessRuleException;
use App\Website\Dto\WebsiteIdentityInput;
use App\Website\Enum\WebsiteStatus;
use App\Website\Event\WebsiteVersionPublishedEvent;
use App\Website\Event\WebsiteVersionScheduledEvent;
use App\Website\Exception\WebsiteNotEditableException;
use App\Website\Helper\WebsiteVersionHelper;
use App\Website\Provider\WebsiteProvider;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class WebsiteVersionService
{
    private const IDENTITY_FIELDS = [
        'name' => 'Nom de la version',
        'description' => 'Note',
        'siteName' => 'Nom du site',
        'domain' => 'Domaine',
        'tagline' => 'Accroche',
        'siteDescription' => 'Description',
        'seoTitle' => 'Titre SEO',
        'contactEmail' => 'E-mail de contact',
    ];

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
        $copy = (new WebsiteConfig())
            ->setCode(WebsiteVersionHelper::nextCode($this->websites->findAllCodes()))
            ->setName('Copie de ' . $source->getCode())
            ->setDescription('Créée à partir de la ' . $source->getCode())
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
        $this->logger->log(ActivityType::VERSION, sprintf('Brouillon créé à partir de la %s', $source->getCode()), $actor, $copy, $copy);
        $this->orm->flush();

        return $copy;
    }

    public function updateIdentity(WebsiteConfig $website, WebsiteIdentityInput $input, ?User $actor = null): WebsiteConfig
    {
        $this->assertEditable($website);

        $changes = [];

        foreach (self::IDENTITY_FIELDS as $field => $label) {
            $value = $input->{$field};

            if ($value === null) {
                continue;
            }

            $value = trim($value);
            $current = $website->{'get' . ucfirst($field)}();

            if ($value === (string) $current) {
                continue;
            }

            $website->{'set' . ucfirst($field)}($value);
            $changes[$field] = ['from' => $current, 'to' => $value];
            $this->logger->log(ActivityType::IDENTITY, sprintf('%s : %s → %s', $label, $current ?? '—', $value), $actor, $website, $website, [$field => $changes[$field]]);
        }

        if ($changes !== []) {
            $this->orm->flush();
        }

        return $website;
    }

    public function schedule(WebsiteConfig $website, DateTimeImmutable $scheduledAt, ?User $actor = null): WebsiteConfig
    {
        $this->assertEditable($website);

        if ($scheduledAt <= new DateTimeImmutable()) {
            throw new BusinessRuleException('La date de mise en ligne doit être dans le futur.');
        }

        $alreadyScheduled = $this->websites->findScheduled();

        if ($alreadyScheduled !== null && $alreadyScheduled !== $website) {
            throw new BusinessRuleException('La {code} est déjà planifiée. Annulez sa planification avant d\'en planifier une autre.', ['code' => $alreadyScheduled->getCode()]);
        }

        $this->assertPublishable($website);
        $this->priceChanges->assertNotice($website, $scheduledAt);

        $website->setStatus(WebsiteStatus::SCHEDULED)->setScheduledAt($scheduledAt);
        $this->logger->log(ActivityType::VERSION, sprintf('Mise en ligne planifiée le %s', $scheduledAt->format('d/m/Y à H:i')), $actor, $website, $website);
        $this->orm->flush();

        $this->events->dispatch(new WebsiteVersionScheduledEvent($website));

        return $website;
    }

    public function unschedule(WebsiteConfig $website, ?User $actor = null): WebsiteConfig
    {
        if ($website->getStatus() !== WebsiteStatus::SCHEDULED) {
            throw new BusinessRuleException('La {code} n\'est pas planifiée.', ['code' => $website->getCode()]);
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

        if (!$wasScheduled || $website->getScheduledAt() === null || $website->getScheduledAt() > $now) {
            $this->priceChanges->assertNotice($website, $now, $now);
        }

        $previous = $this->websites->findLive();

        $this->orm->transactional(function () use ($website, $actor, $now, $previous): void {
            if ($previous !== null && $previous !== $website) {
                $previous->setStatus(WebsiteStatus::ARCHIVED)->setArchivedAt($now);
            }

            $website->setStatus(WebsiteStatus::LIVE)->setPublishedAt($now)->setScheduledAt(null);

            $message = $previous !== null && $previous !== $website
                ? sprintf('Mise en ligne, la %s est archivée', $previous->getCode())
                : 'Mise en ligne';

            $this->logger->log(ActivityType::VERSION, $message, $actor, $website, $website);
            $this->orm->flush();
        });

        $this->provider->reset();
        $this->events->dispatch(new WebsiteVersionPublishedEvent($website, $previous !== $website ? $previous : null, $wasScheduled));

        return $website;
    }

    public function restore(WebsiteConfig $source, ?User $actor = null): WebsiteConfig
    {
        if ($source->getStatus() !== WebsiteStatus::ARCHIVED) {
            throw new BusinessRuleException('Seule une version archivée peut être restaurée. La {code} est {status}.', [
                'code' => $source->getCode(),
                'status' => mb_strtolower($source->getStatus()->label()),
            ]);
        }

        $scheduled = $this->websites->findScheduled();

        if ($scheduled !== null) {
            throw new BusinessRuleException('La {code} est planifiée. Annulez sa planification avant de restaurer une ancienne version.', ['code' => $scheduled->getCode()]);
        }

        $copy = $this->duplicate($source, $actor);
        $copy->setName('Retour à la ' . $source->getCode())->setDescription('Restauration de la ' . $source->getCode());
        $this->orm->flush();

        return $this->publish($copy, $actor);
    }

    public function delete(WebsiteConfig $website, ?User $actor = null): void
    {
        if (in_array($website->getStatus(), [WebsiteStatus::LIVE, WebsiteStatus::SCHEDULED], true)) {
            throw new BusinessRuleException('La {code} est {status} : elle ne peut pas être supprimée.', [
                'code' => $website->getCode(),
                'status' => mb_strtolower($website->getStatus()->label()),
            ]);
        }

        foreach ($website->getPlans() as $plan) {
            if ($this->subscriptions->count(['plan' => $plan]) > 0) {
                throw new BusinessRuleException('La {code} ne peut pas être supprimée : des abonnements utilisent encore sa formule « {plan} ».', [
                    'code' => $website->getCode(),
                    'plan' => $plan->getName(),
                ]);
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

    private function assertPublishable(WebsiteConfig $website): void
    {
        if ($website->getRule() === null) {
            throw new BusinessRuleException('La {code} n\'a pas de règles : elle ne peut pas être mise en ligne.', ['code' => $website->getCode()]);
        }

        foreach ($website->getPlans() as $plan) {
            if ($plan->isActive()) {
                return;
            }
        }

        throw new BusinessRuleException('La {code} n\'a aucune formule active : elle ne peut pas être mise en ligne.', ['code' => $website->getCode()]);
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
        $plan = $promoCode->getPlan();

        return (new WebsitePromoCode())
            ->setCode((string) $promoCode->getCode())
            ->setDescription($promoCode->getDescription())
            ->setPlan($plan !== null ? ($plans[$plan->getCode()] ?? null) : null)
            ->setDiscountPercent($promoCode->getDiscountPercent())
            ->setFreeMonths($promoCode->getFreeMonths())
            ->setDurationMonths($promoCode->getDurationMonths())
            ->setMaxUses($promoCode->getMaxUses())
            ->setStartsAt($promoCode->getStartsAt())
            ->setEndsAt($promoCode->getEndsAt())
            ->setActive($promoCode->isActive());
    }
}