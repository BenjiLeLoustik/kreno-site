<?php

namespace App\Company\Provider;

use App\Company\Enum\MemberStatus;
use App\Company\Security\PermissionResolver;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Entity\User\User;
use NeoPHP\Component\Session\SessionManagerInterface;

class CurrentCompanyProvider
{
    private const SESSION_KEY = 'pro.establishment_id';

    public function __construct(
        private SessionManagerInterface $session,
        private PermissionResolver $resolver,
    ) {
    }

    public function getMembership(User $user): ?CompanyMember
    {
        foreach ($user->getMemberships() as $membership) {
            if ($membership->getStatus() === MemberStatus::ACTIVE) {
                return $membership;
            }
        }

        return null;
    }

    public function getCompany(User $user): ?Company
    {
        return $this->getMembership($user)?->getCompany();
    }

    public function getEstablishments(CompanyMember $member): array
    {
        if ($this->resolver->isCompanyAdmin($member)) {
            return $member->getCompany()?->getEstablishments()->toArray() ?? [];
        }

        $establishments = [];

        foreach ($member->getEstablishmentRoles() as $role) {
            if ($role->getEstablishment() !== null) {
                $establishments[] = $role->getEstablishment();
            }
        }

        return $establishments;
    }

    public function getCurrentEstablishment(CompanyMember $member): ?CompanyEstablishment
    {
        $establishments = $this->getEstablishments($member);
        $selectedId = $this->session->get(self::SESSION_KEY);

        foreach ($establishments as $establishment) {
            if ($establishment->getId() === $selectedId) {
                return $establishment;
            }
        }

        return $establishments[0] ?? null;
    }

    public function switchTo(CompanyMember $member, int $establishmentId): ?CompanyEstablishment
    {
        foreach ($this->getEstablishments($member) as $establishment) {
            if ($establishment->getId() === $establishmentId) {
                $this->session->set(self::SESSION_KEY, $establishmentId);

                return $establishment;
            }
        }

        return null;
    }
}