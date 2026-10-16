<?php

namespace App\Website\Service;

use App\Entity\User\User;
use App\Entity\Website\WebsiteConfig;
use App\Entity\Website\WebsitePlan;
use App\Entity\Website\WebsiteRule;
use App\Repository\User\UserRepository;
use App\Repository\Website\WebsiteConfigRepository;
use App\Shared\Exception\BusinessRuleException;
use App\User\Enum\UserRole;
use App\Website\Dto\WebsiteIdentityInput;
use App\Website\Dto\WebsitePlanInput;
use App\Website\Dto\WebsiteRuleInput;
use App\Website\Enum\PlanBilling;
use App\Website\Enum\PriceChangePolicy;
use App\Website\Enum\WebsiteStatus;
use App\Website\Helper\WebsiteVersionHelper;
use DateTimeImmutable;
use NeoPHP\Package\Orm\OrmManagerInterface;
use NeoPHP\Package\Security\Hasher\UserPasswordHasher;

class WebsiteInstallService
{
    public const PRICE_CHANGE_NOTICE_DAYS = 30;

    public function __construct(
        private OrmManagerInterface $orm,
        private WebsiteConfigRepository $websites,
        private UserRepository $users,
        private UserPasswordHasher $hasher,
        private WebsiteVersionService $versions,
    ) {
    }

    public function isInstalled(): bool
    {
        return $this->websites->findLive() !== null;
    }

    public function hasAdmin(): bool
    {
        foreach ($this->users->findStaff() as $user) {
            if (in_array(UserRole::ADMIN->value, $user->getRoles(), true)) {
                return true;
            }
        }

        return false;
    }

    public function createAdmin(string $email, string $firstName, string $password): User
    {
        $email = mb_strtolower(trim($email));

        if ($this->users->emailExists($email)) {
            throw new BusinessRuleException('Un compte existe déjà avec cette adresse : connecte-toi avec ce compte puis reviens sur l\'installation.');
        }

        $user = (new User())
            ->setEmail($email)
            ->setFirstName(trim($firstName))
            ->setRoles([UserRole::ADMIN->value])
            ->setEmailVerifiedAt(new DateTimeImmutable());

        $user->setPassword($this->hasher->hashPassword($user, $password));
        $this->orm->persist($user);
        $this->orm->flush();

        return $user;
    }

    public function install(WebsiteIdentityInput $identity, WebsiteRuleInput $rules, array $plans, ?User $actor = null): WebsiteConfig
    {
        $this->assertInstallable($rules, $plans);

        $website = (new WebsiteConfig())
            ->setCode(WebsiteVersionHelper::nextCode($this->websites->findAllCodes()))
            ->setName(trim((string) $identity->name) ?: 'Lancement')
            ->setDescription(trim((string) $identity->description) ?: 'Version créée à l\'installation')
            ->setSiteName(trim((string) $identity->siteName))
            ->setDomain(trim((string) $identity->domain))
            ->setTagline(trim((string) $identity->tagline))
            ->setSiteDescription(trim((string) $identity->siteDescription))
            ->setSeoTitle(trim((string) $identity->seoTitle) ?: null)
            ->setContactEmail(trim((string) $identity->contactEmail))
            ->setPricingName(sprintf('Tarifs %s', (new DateTimeImmutable())->format('Y')))
            ->setPriceChangePolicy(PriceChangePolicy::KEEP_UNTIL_RENEWAL)
            ->setPriceChangeNoticeDays(self::PRICE_CHANGE_NOTICE_DAYS)
            ->setStatus(WebsiteStatus::DRAFT)
            ->setCreatedBy($actor);

        $website->setRule($this->rule($rules)->setWebsite($website));

        foreach (array_values($plans) as $position => $input) {
            $website->addPlan($this->plan($input, $position));
        }

        $this->orm->persist($website);
        $this->orm->flush();

        return $this->versions->publish($website, $actor);
    }

    public function assertInstallable(WebsiteRuleInput $rules, array $plans): void
    {
        if ($this->isInstalled()) {
            throw new BusinessRuleException('Le site est déjà installé : une version est en ligne.');
        }

        if (array_filter($plans, static fn (WebsitePlanInput $plan): bool => (bool) $plan->active) === []) {
            throw new BusinessRuleException('Activez au moins une formule pour les professionnels.');
        }

        foreach (get_object_vars($rules) as $value) {
            if ($value === null) {
                throw new BusinessRuleException('Toutes les règles de la plateforme doivent être renseignées.');
            }
        }

        if ($rules->minDiscount >= $rules->maxDiscount) {
            throw new BusinessRuleException('La remise minimale doit être inférieure à la remise maximale.');
        }

        if ($rules->defaultDiscount < $rules->minDiscount || $rules->defaultDiscount > $rules->maxDiscount) {
            throw new BusinessRuleException('La remise par défaut doit être comprise entre la minimale et la maximale.');
        }

        if ($rules->autoRemovalMinutes >= $rules->freeCancellationMinutes) {
            throw new BusinessRuleException('Le retrait automatique doit arriver avant la fin de l\'annulation gratuite.');
        }
    }

    private function rule(WebsiteRuleInput $rules): WebsiteRule
    {
        return (new WebsiteRule())
            ->setFreeCancellationMinutes((int) $rules->freeCancellationMinutes)
            ->setBookingLeadMinutes((int) $rules->bookingLeadMinutes)
            ->setMaxUpcomingBookingsPerUser((int) $rules->maxUpcomingBookingsPerUser)
            ->setToleratedNoShows((int) $rules->toleratedNoShows)
            ->setNoShowWindowDays((int) $rules->noShowWindowDays)
            ->setSuspensionDays((int) $rules->suspensionDays)
            ->setMinDiscount((int) $rules->minDiscount)
            ->setMaxDiscount((int) $rules->maxDiscount)
            ->setDefaultDiscount((int) $rules->defaultDiscount)
            ->setPublicationWindowDays((int) $rules->publicationWindowDays)
            ->setAutoRemovalMinutes((int) $rules->autoRemovalMinutes);
    }

    private function plan(WebsitePlanInput $input, int $position): WebsitePlan
    {
        $billing = $input->billing ?? PlanBilling::PER_BOOKING;
        $subscription = $billing === PlanBilling::SUBSCRIPTION;

        return (new WebsitePlan())
            ->setCode((string) $input->code)
            ->setName((string) $input->name)
            ->setDescription($input->description)
            ->setBilling($billing)
            ->setPrice((int) $input->price)
            ->setYearlyPrice($subscription ? $input->yearlyPrice : null)
            ->setVatRate($input->vatRate ?? 20)
            ->setTrialDays($subscription ? (int) $input->trialDays : 0)
            ->setCommitmentMonths($subscription ? (int) $input->commitmentMonths : 0)
            ->setMaxEstablishments($subscription && !$input->unlimitedEstablishments ? $input->maxEstablishments : null)
            ->setCommissionFree($subscription && (bool) $input->commissionFree)
            ->setFeatured((bool) $input->featured)
            ->setAdvancedStats((bool) $input->advancedStats)
            ->setActive((bool) $input->active)
            ->setPosition($position);
    }
}