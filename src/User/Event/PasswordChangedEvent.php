<?php

namespace App\User\Event;

use App\Entity\User\User;

class PasswordChangedEvent
{
    public function __construct(
        public User $user
    ) {
    }
}