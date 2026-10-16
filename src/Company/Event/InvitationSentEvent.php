<?php

namespace App\Company\Event;

use App\Entity\Company\CompanyInvitation;

class InvitationSentEvent
{
    public function __construct(
        public CompanyInvitation $invitation,
        public string $token,
    ) {
    }
}