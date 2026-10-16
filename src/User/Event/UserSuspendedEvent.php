<?php

namespace App\User\Event;

use App\Entity\User\User;

class UserSuspendedEvent
{
    public function __construct(
        public User $user,
        public string $reason,
        public bool $automatic,
    ) {
    }
}