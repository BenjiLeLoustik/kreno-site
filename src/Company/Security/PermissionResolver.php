<?php

namespace App\Company\Security;

use App\Company\Enum\MemberStatus;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Entity\Establishment\EstablishmentMember;
use App\Entity\User\User;

class PermissionResolver
{
    public function findMember(User $user, Company $company): ?CompanyMember
    {
        foreach ($user->getMemberships() as $membership) {
            if ($membership->getCompany() === $company && $membership->getStatus() === MemberStatus::ACTIVE) {
                return $membership;
            }
        }

        return null;
    }

    public function findEstablishmentRole(CompanyMember $member, CompanyEstablishment $establishment): ?EstablishmentMember
    {
        foreach ($member->getEstablishmentRoles() as $role) {
            if ($role->getEstablishment() === $establishment) {
                return $role;
            }
        }

        return null;
    }

    public function isCompanyAdmin(CompanyMember $member): bool
    {
        return $member->getRole() !== null && $member->getStatus() === MemberStatus::ACTIVE;
    }

    public function can(User $user, string $permission, Company|CompanyEstablishment $subject): bool
    {
        $company = $subject instanceof CompanyEstablishment ? $subject->getCompany() : $subject;

        if ($company === null) {
            return false;
        }

        $member = $this->findMember($user, $company);

        return $member !== null && $this->memberCan($member, $permission, $subject);
    }

    public function memberCan(CompanyMember $member, string $permission, Company|CompanyEstablishment $subject): bool
    {
        if ($member->getStatus() !== MemberStatus::ACTIVE) {
            return false;
        }

        if ($this->isCompanyAdmin($member)) {
            return in_array($permission, Permission::ALL, true);
        }

        if (!$subject instanceof CompanyEstablishment) {
            return false;
        }

        $role = $this->findEstablishmentRole($member, $subject);

        if ($role === null) {
            return false;
        }

        $key = $role->getRole()->value;

        if (in_array($permission, Permission::ESTABLISHMENT_ROLES[$key] ?? [], true)) {
            return true;
        }

        return (Permission::ON_DEMAND[$key] ?? null) === $permission && $role->isCanManageBookings();
    }

    public function permissionsFor(CompanyMember $member, Company|CompanyEstablishment $subject): array
    {
        $granted = [];

        foreach (Permission::ALL as $permission) {
            $granted[$permission] = $this->memberCan($member, $permission, $subject);
        }

        return $granted;
    }
}