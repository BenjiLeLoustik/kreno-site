<?php

namespace App\Company\Event;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;

class MemberRoleChangedEvent
{
    public function __construct(
        public CompanyMember $member,
        public string $previousRole,
        public string $newRole,
        public ?CompanyEstablishment $establishment = null,
    ) {
    }
}