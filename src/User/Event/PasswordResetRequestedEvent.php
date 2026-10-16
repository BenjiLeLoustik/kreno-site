<?php

namespace App\User\Event;

use App\Entity\User\User;

class PasswordResetRequestedEvent
{
    public function __construct(
        public User $user,
        public string $resetToken,
        public string $context = 'client',
    ) {
    }
}