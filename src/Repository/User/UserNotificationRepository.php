<?php

namespace App\Repository\User;

use App\Entity\User\User;
use App\Entity\User\UserNotification;
use NeoPHP\Package\Orm\Contract\AbstractRepository;

class UserNotificationRepository extends AbstractRepository
{
    protected string $entityClass = UserNotification::class;

    public function findLatestForUser(User $user, int $limit = 30): array
    {
        return $this->findBy(['user' => $user], ['createdAt' => 'DESC'], $limit);
    }

    public function findUnreadForUser(User $user): array
    {
        return $this->findBy(['user' => $user, 'readAt' => null]);
    }

    public function countUnread(User $user): int
    {
        return $this->count(['user' => $user, 'readAt' => null]);
    }
}