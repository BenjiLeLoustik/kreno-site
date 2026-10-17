<?php

namespace App\Company\Service;

use App\Company\Enum\CompanyRole;
use App\Company\Enum\ProSignupStep;
use App\Company\Exception\CompanyRequiredException;
use App\Company\Exception\NoCompanyMembershipException;
use App\Company\Exception\SignupEstablishmentRequiredException;
use App\Company\Exception\SignupOwnerRequiredException;
use App\Company\Provider\CurrentCompanyProvider;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentStatus;
use App\Repository\Company\CompanyPaymentMethodRepository;
use App\Repository\Company\CompanySubscriptionRepository;
use NeoPHP\Component\Session\SessionManagerInterface;

class ProSignupFlow
{
    public const PAGE_SKIPPED = 'pro.signup.page_skipped';

    public const PAYMENT_STATUSES = [EstablishmentStatus::DRAFT, EstablishmentStatus::REJECTED];

    public function __construct(
        private CurrentCompanyProvider $context,
        private CompanySubscriptionRepository $subscriptions,
        private CompanyPaymentMethodRepository $methods,
        private SessionManagerInterface $session,
    ) {
    }

    public function currentStep(?User $user): ProSignupStep
    {
        if ($user === null || $this->context->getMembership($user) === null) {
            return ProSignupStep::ACCOUNT;
        }

        if ($user->getEmailVerifiedAt() === null) {
            return ProSignupStep::VERIFY;
        }

        $establishment = $this->draft($user);

        if ($establishment === null) {
            return ProSignupStep::ESTABLISHMENT;
        }

        return $this->establishmentStep($establishment);
    }

    public function draft(?User $user): ?CompanyEstablishment
    {
        if ($user === null) {
            return null;
        }

        $establishment = $this->context->getMembership($user)?->getCompany()?->getEstablishments()->first();

        if (!$establishment instanceof CompanyEstablishment) {
            return null;
        }

        return $establishment;
    }

    public function member(?User $user): CompanyMember
    {
        $member = null;

        if ($user !== null) {
            $member = $this->context->getMembership($user);
        }

        if ($member === null) {
            throw new NoCompanyMembershipException();
        }

        return $member;
    }

    public function owner(CompanyMember $member): CompanyMember
    {
        if ($member->getRole() !== CompanyRole::OWNER) {
            throw new SignupOwnerRequiredException();
        }

        return $member;
    }

    public function company(CompanyMember $member): Company
    {
        $company = $this->owner($member)->getCompany();

        if ($company === null) {
            throw new CompanyRequiredException();
        }

        return $company;
    }

    public function establishment(CompanyMember $member): CompanyEstablishment
    {
        $establishment = $this->draft($this->owner($member)->getUser());

        if ($establishment === null) {
            throw new SignupEstablishmentRequiredException();
        }

        return $establishment;
    }

    public function skipPage(CompanyMember $member): void
    {
        $this->establishment($member);
        $this->session->set(self::PAGE_SKIPPED, true);
    }

    public function pageCompleted(): void
    {
        $this->session->remove(self::PAGE_SKIPPED);
    }

    private function establishmentStep(CompanyEstablishment $establishment): ProSignupStep
    {
        if ($establishment->getServices()->isEmpty()) {
            return ProSignupStep::SERVICES;
        }

        if ($establishment->getOpeningHours()->isEmpty() && $this->session->get(self::PAGE_SKIPPED) !== true) {
            return ProSignupStep::PAGE;
        }

        $company = $establishment->getCompany();

        if ($company === null || !$this->hasSubscription($company)) {
            return ProSignupStep::PLAN;
        }

        if ($this->methods->findForCompany($company) === []) {
            return ProSignupStep::PAYMENT;
        }

        return $this->statusStep($establishment);
    }

    private function statusStep(CompanyEstablishment $establishment): ProSignupStep
    {
        if (in_array($establishment->getStatus(), self::PAYMENT_STATUSES, true)) {
            return ProSignupStep::PAYMENT;
        }

        if ($establishment->getStatus() === EstablishmentStatus::PENDING_REVIEW) {
            return ProSignupStep::PENDING;
        }

        return ProSignupStep::DONE;
    }

    private function hasSubscription(Company $company): bool
    {
        return $this->subscriptions->findPending($company) !== null
            || $this->subscriptions->findCurrent($company) !== null;
    }
}