<?php

namespace App\User\Event;

use App\Entity\User\User;
use App\User\Enum\UserRole;

class StaffInvitedEvent
{
    public function __construct(
        private User $user,
        private UserRole $role,
        private User $invitedBy,
        private ?string $passwordToken = null,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function getInvitedBy(): User
    {
        return $this->invitedBy;
    }

    public function getPasswordToken(): ?string
    {
        return $this->passwordToken;
    }
}