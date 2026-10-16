<?php

namespace App\User\Presenter;

use App\Entity\User\User;
use App\Shared\Helper\DateHelper;
use App\User\Helper\UserNameHelper;

class UserPresenter
{
    public function summary(User $user): array
    {
        return [
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'shortName' => UserNameHelper::shortName($user),
            'initials' => UserNameHelper::initials($user),
        ];
    }

    public function profile(User $user): array
    {
        return $this->summary($user) + [
                'lastName' => $user->getLastName(),
                'fullName' => UserNameHelper::fullName($user),
                'email' => $user->getEmail(),
                'emailVerified' => $user->getEmailVerifiedAt() !== null,
                'phone' => $user->getPhone(),
                'emailNotifications' => $user->isEmailNotifications(),
                'status' => $user->getStatus()->value,
                'statusLabel' => $user->getStatus()->label(),
                'suspendedUntil' => DateHelper::iso($user->getSuspendedUntil()),
                'memberSince' => DateHelper::iso($user->getCreatedAt()),
                'isPro' => !$user->getMemberships()->isEmpty(),
            ];
    }

    public function admin(User $user, array $stats = []): array
    {
        return $this->profile($user) + [
                'roles' => $user->getRoles(),
                'statusReason' => $user->getStatusReason(),
                'lastLoginAt' => DateHelper::iso($user->getLastLoginAt()),
                'createdAt' => DateHelper::iso($user->getCreatedAt()),
                'stats' => $stats,
            ];
    }
}