<?php

namespace App\Establishment\Provider;

use App\Company\Provider\CurrentCompanyProvider;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\User\User;

class ProEntryProvider
{
    public function __construct(
        private CurrentCompanyProvider $context,
    ) {
    }

    public function dashboardEstablishment(User $user): ?CompanyEstablishment
    {
        if ($user->getEmailVerifiedAt() === null) {
            return null;
        }

        return $this->welcomeEstablishment($user);
    }

    public function welcomeEstablishment(User $user): ?CompanyEstablishment
    {
        $member = $this->context->getMembership($user);

        if ($member === null) {
            return null;
        }

        return $this->context->getCurrentEstablishment($member);
    }
}