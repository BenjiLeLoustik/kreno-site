<?php

namespace App\User\Navbar;

use App\Entity\User\User;
use App\Repository\User\UserNotificationRepository;

class NotificationBuilder extends AbstractAccountNavbarBuilder
{
    public function __construct(
        private UserNotificationRepository $notifications,
    ) {
    }

    public function getKey(): string
    {
        return 'notifications';
    }

    public function getLabel(): string
    {
        return 'Notifications';
    }

    public function getRoute(): string
    {
        return 'account_notifications';
    }

    public function getIcon(): string
    {
        return 'inbox';
    }

    public function getPosition(): int
    {
        return 60;
    }

    public function getBadge(User $user): int
    {
        return $this->notifications->countUnread($user);
    }
}