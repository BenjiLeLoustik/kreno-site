<?php

namespace App\User\Service;

use App\Entity\User\User;
use App\Entity\User\UserNotification;
use App\Repository\User\UserNotificationRepository;
use App\User\Enum\NotificationType;
use DateTimeImmutable;
use NeoPHP\Package\Orm\OrmManagerInterface;

class NotificationService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private UserNotificationRepository $notifications,
    ) {
    }

    public function notify(User $user, NotificationType $type, string $title, ?string $body = null, ?string $link = null, bool $flush = true): UserNotification
    {
        $notification = (new UserNotification())
            ->setType($type)
            ->setTitle(mb_substr($title, 0, 160))
            ->setBody($body !== null ? mb_substr($body, 0, 255) : null)
            ->setLink($link);

        $user->addNotification($notification);
        $this->orm->persist($notification);

        if ($flush) {
            $this->orm->flush();
        }

        return $notification;
    }

    public function markRead(UserNotification $notification): void
    {
        if ($notification->getReadAt() === null) {
            $notification->setReadAt(new DateTimeImmutable());
            $this->orm->flush();
        }
    }

    public function markAllRead(User $user): int
    {
        $now = new DateTimeImmutable();
        $unread = $this->notifications->findUnreadForUser($user);

        foreach ($unread as $notification) {
            $notification->setReadAt($now);
        }

        $this->orm->flush();

        return count($unread);
    }
}