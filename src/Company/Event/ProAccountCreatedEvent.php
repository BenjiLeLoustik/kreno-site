<?php

namespace App\Company\Event;

use App\Entity\Company\Company;
use App\Entity\User\User;

class ProAccountCreatedEvent
{
    public function __construct(
        public User $user,
        public Company $company,
        public string $verificationCode,
    ) {
    }
}