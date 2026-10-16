<?php

namespace App\Company\Event;

use App\Entity\Company\CompanyMember;

class MemberRemovedEvent
{
    public function __construct(
        public CompanyMember $member,
    ) {
    }
}