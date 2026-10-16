<?php

namespace App\User\Provider;

use App\Entity\User\User;
use App\Entity\User\UserNotification;
use App\Repository\User\UserNotificationRepository;
use App\User\Exception\NotificationNotFoundException;
use App\User\Presenter\NotificationPresenter;

class NotificationPageProvider
{
    public function __construct(
        private UserNotificationRepository $notifications,
        private NotificationPresenter $presenter,
    ) {
    }

    public function index(User $user): array
    {
        return [
            'unread' => $this->notifications->countUnread($user),
            'notifications' => $this->presenter->presentAll($this->notifications->findLatestForUser($user)),
        ];
    }

    public function owned(int $id, User $user): UserNotification
    {
        $notification = $this->notifications->find($id);

        if (!$notification instanceof UserNotification || $notification->getUser() !== $user) {
            throw new NotificationNotFoundException();
        }

        return $notification;
    }

    public function link(UserNotification $notification): ?string
    {
        $link = $notification->getLink();

        if ($link === null || $link === '') {
            return null;
        }

        return $link;
    }
}