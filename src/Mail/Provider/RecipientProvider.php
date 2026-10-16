<?php

namespace App\Mail\Provider;

use App\Company\Enum\CompanyRole;
use App\Company\Enum\MemberStatus;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
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
            if ($this->isActive($member) && $member->getRole() !== null && $member->getUser() !== null) {
                $addresses[] = $this->mail->address($member->getUser());
            }
        }

        return $addresses;
    }

    public function billing(?Company $company): array
    {
        $owner = $this->owner($company);

        return array_values(array_filter([
            $owner !== null ? $this->mail->address($owner) : null,
            $company?->getConfig()?->getBillingEmail(),
        ]));
    }

    public function establishment(?CompanyEstablishment $establishment, bool $withAdmins = true): array
    {
        $addresses = $withAdmins ? $this->admins($establishment?->getCompany()) : [];

        foreach ($establishment?->getMembers() ?? [] as $staff) {
            $member = $staff->getMember();

            if ($member !== null && $this->isActive($member) && $member->getUser() !== null && $staff->getRole() === EstablishmentRole::MANAGER) {
                $addresses[] = $this->mail->address($member->getUser());
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
            $member = $staff->getMember();

            if ($member !== null && $this->isActive($member) && $member->getUser() !== null && ($staff->getRole() === EstablishmentRole::RECEPTIONIST || $staff->isCanManageBookings())) {
                $addresses[] = $this->mail->address($member->getUser());
            }
        }

        return array_values(array_unique($addresses));
    }

    private function isActive(CompanyMember $member): bool
    {
        return $member->getStatus() === MemberStatus::ACTIVE;
    }
}