<?php

namespace App\Company\Event;

use App\Entity\Company\CompanyMember;

class MemberJoinedEvent
{
    public function __construct(
        public CompanyMember $member,
    ) {
    }
}