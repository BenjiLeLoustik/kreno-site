<?php

namespace App\Website\Service;

use App\Entity\User\User;
use App\Entity\Website\WebsiteConfig;
use App\Entity\Website\WebsitePlan;
use App\Entity\Website\WebsiteRule;
use App\Repository\User\UserRepository;
use App\Repository\Website\WebsiteConfigRepository;
use App\User\Enum\UserRole;
use App\Website\Dto\WebsiteIdentityInput;
use App\Website\Dto\WebsitePlanInput;
use App\Website\Dto\WebsiteRuleInput;
use App\Website\Enum\PlanBilling;
use App\Website\Enum\PriceChangePolicy;
use App\Website\Enum\WebsiteStatus;
use App\Website\Exception\AdminEmailExistsException;
use App\Website\Exception\AlreadyInstalledException;
use App\Website\Exception\AutoRemovalTooLateException;
use App\Website\Exception\DefaultDiscountOutOfRangeException;
use App\Website\Exception\MinDiscountAboveMaxException;
use App\Website\Exception\MissingRulesException;
use App\Website\Exception\NoActivePlanException;
use App\Website\Helper\WebsiteVersionHelper;
use DateTimeImmutable;
use NeoPHP\Package\Orm\OrmManagerInterface;
use NeoPHP\Package\Security\Hasher\UserPasswordHasher;

class WebsiteInstallService
{
    public const PRICE_CHANGE_NOTICE_DAYS = 30;

    public const DEFAULT_VAT_RATE = 20;

    public const DEFAULT_NAME = 'Lancement';

    public const DEFAULT_DESCRIPTION = 'Version créée à l\'installation';

    public const PRICING_NAME = 'Tarifs %s';

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
            throw new AdminEmailExistsException();
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

    public function install(
        WebsiteIdentityInput $identity,
        WebsiteRuleInput $rules,
        array $plans,
        ?User $actor = null,
    ): WebsiteConfig {
        $this->assertInstallable($rules, $plans);

        $website = $this->website($identity, $actor);
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
            throw new AlreadyInstalledException();
        }

        if (!$this->hasActivePlan($plans)) {
            throw new NoActivePlanException();
        }

        foreach (get_object_vars($rules) as $value) {
            if ($value === null) {
                throw new MissingRulesException();
            }
        }

        if ($rules->minDiscount >= $rules->maxDiscount) {
            throw new MinDiscountAboveMaxException();
        }

        if ($rules->defaultDiscount < $rules->minDiscount || $rules->defaultDiscount > $rules->maxDiscount) {
            throw new DefaultDiscountOutOfRangeException();
        }

        if ($rules->autoRemovalMinutes >= $rules->freeCancellationMinutes) {
            throw new AutoRemovalTooLateException();
        }
    }

    private function hasActivePlan(array $plans): bool
    {
        foreach ($plans as $plan) {
            if ($plan instanceof WebsitePlanInput && $plan->active) {
                return true;
            }
        }

        return false;
    }

    private function website(WebsiteIdentityInput $identity, ?User $actor): WebsiteConfig
    {
        return (new WebsiteConfig())
            ->setCode(WebsiteVersionHelper::nextCode($this->websites->findAllCodes()))
            ->setName(trim((string) $identity->name) ?: self::DEFAULT_NAME)
            ->setDescription(trim((string) $identity->description) ?: self::DEFAULT_DESCRIPTION)
            ->setSiteName(trim((string) $identity->siteName))
            ->setDomain(trim((string) $identity->domain))
            ->setTagline(trim((string) $identity->tagline))
            ->setSiteDescription(trim((string) $identity->siteDescription))
            ->setSeoTitle(trim((string) $identity->seoTitle) ?: null)
            ->setContactEmail(trim((string) $identity->contactEmail))
            ->setPricingName(sprintf(self::PRICING_NAME, (new DateTimeImmutable())->format('Y')))
            ->setPriceChangePolicy(PriceChangePolicy::KEEP_UNTIL_RENEWAL)
            ->setPriceChangeNoticeDays(self::PRICE_CHANGE_NOTICE_DAYS)
            ->setStatus(WebsiteStatus::DRAFT)
            ->setCreatedBy($actor);
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

        $plan = (new WebsitePlan())
            ->setCode((string) $input->code)
            ->setName((string) $input->name)
            ->setDescription($input->description)
            ->setBilling($billing)
            ->setPrice((int) $input->price)
            ->setVatRate($input->vatRate ?? self::DEFAULT_VAT_RATE)
            ->setFeatured((bool) $input->featured)
            ->setAdvancedStats((bool) $input->advancedStats)
            ->setActive((bool) $input->active)
            ->setPosition($position);

        if ($billing === PlanBilling::SUBSCRIPTION) {
            return $this->applySubscription($plan, $input);
        }

        return $plan
            ->setYearlyPrice(null)
            ->setTrialDays(0)
            ->setCommitmentMonths(0)
            ->setMaxEstablishments(null)
            ->setCommissionFree(false);
    }

    private function applySubscription(WebsitePlan $plan, WebsitePlanInput $input): WebsitePlan
    {
        return $plan
            ->setYearlyPrice($input->yearlyPrice)
            ->setTrialDays((int) $input->trialDays)
            ->setCommitmentMonths((int) $input->commitmentMonths)
            ->setMaxEstablishments($this->maxEstablishments($input))
            ->setCommissionFree((bool) $input->commissionFree);
    }

    private function maxEstablishments(WebsitePlanInput $input): ?int
    {
        if ($input->unlimitedEstablishments) {
            return null;
        }

        return $input->maxEstablishments;
    }
}