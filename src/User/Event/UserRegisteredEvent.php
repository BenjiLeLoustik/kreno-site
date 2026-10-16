<?php

namespace App\User\Event;

use App\Entity\User\User;

class UserRegisteredEvent
{
    public function __construct(
        public User $user,
        public string $verificationToken,
    ) {
    }
}