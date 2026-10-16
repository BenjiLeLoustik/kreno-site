<?php

namespace App\Mail\Provider;

use App\Company\Enum\CompanyRole;
use App\Company\Enum\MemberStatus;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Entity\Establishment\EstablishmentMember;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentRole;
use App\Mail\Service\MailService;

class RecipientProvider
{
    public function __construct(
        private MailService $mail,
    ) {
    }

    public function owner(?Company $company): ?User
    {
        foreach ($company?->getMembers() ?? [] as $member) {
            if ($member->getRole() === CompanyRole::OWNER && $member->getUser() !== null) {
                return $member->getUser();
            }
        }

        return null;
    }

    public function admins(?Company $company): array
    {
        $addresses = [];

        foreach ($company?->getMembers() ?? [] as $member) {
            if ($this->isReachable($member) && $member->getRole() !== null) {
                $addresses[] = $this->mail->address($member->getUser());
            }
        }

        return $addresses;
    }

    public function billing(?Company $company): array
    {
        $addresses = [];
        $owner = $this->owner($company);
        $billingEmail = $company?->getConfig()?->getBillingEmail();

        if ($owner !== null) {
            $addresses[] = $this->mail->address($owner);
        }

        if ($billingEmail !== null && $billingEmail !== '') {
            $addresses[] = $billingEmail;
        }

        return $addresses;
    }

    public function establishment(?CompanyEstablishment $establishment, bool $withAdmins = true): array
    {
        $addresses = [];

        if ($withAdmins) {
            $addresses = $this->admins($establishment?->getCompany());
        }

        foreach ($establishment?->getMembers() ?? [] as $staff) {
            if ($this->isManager($staff)) {
                $addresses[] = $this->mail->address($staff->getMember()->getUser());
            }
        }

        return array_values(array_unique($addresses));
    }

    public function bookingDesk(?CompanyEstablishment $establishment): array
    {
        if ($establishment?->getCompany()?->getConfig()?->isBookingEmailNotifications() === false) {
            return [];
        }

        $addresses = $this->establishment($establishment);

        foreach ($establishment?->getMembers() ?? [] as $staff) {
            if ($this->handlesBookings($staff)) {
                $addresses[] = $this->mail->address($staff->getMember()->getUser());
            }
        }

        return array_values(array_unique($addresses));
    }

    private function isManager(EstablishmentMember $staff): bool
    {
        $member = $staff->getMember();

        if ($member === null || !$this->isReachable($member)) {
            return false;
        }

        return $staff->getRole() === EstablishmentRole::MANAGER;
    }

    private function handlesBookings(EstablishmentMember $staff): bool
    {
        $member = $staff->getMember();

        if ($member === null || !$this->isReachable($member)) {
            return false;
        }

        return $staff->getRole() === EstablishmentRole::RECEPTIONIST || $staff->isCanManageBookings();
    }

    private function isReachable(CompanyMember $member): bool
    {
        return $member->getStatus() === MemberStatus::ACTIVE && $member->getUser() !== null;
    }
}