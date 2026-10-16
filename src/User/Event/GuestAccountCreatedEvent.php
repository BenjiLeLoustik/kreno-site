<?php

namespace App\User\Event;

use App\Entity\User\User;

class GuestAccountCreatedEvent
{
    public function __construct(
        private User $user,
        private string $passwordToken,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getPasswordToken(): string
    {
        return $this->passwordToken;
    }
}