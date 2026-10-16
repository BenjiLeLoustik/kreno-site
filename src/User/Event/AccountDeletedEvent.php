<?php

namespace App\User\Event;

use App\Entity\User\User;

class AccountDeletedEvent
{
    public function __construct(
        public User $user,
        public string $email,
        public string $firstName,
    ) {
    }
}