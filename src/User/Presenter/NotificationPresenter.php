<?php

namespace App\User\Presenter;

use App\Entity\User\UserNotification;
use App\Shared\Helper\DateHelper;

class NotificationPresenter
{
    public function present(UserNotification $notification): array
    {
        return [
            'id' => $notification->getId(),
            'type' => $notification->getType()?->value,
            'typeLabel' => $notification->getType()?->label(),
            'title' => $notification->getTitle(),
            'body' => $notification->getBody(),
            'link' => $notification->getLink(),
            'read' => $notification->getReadAt() !== null,
            'createdAt' => DateHelper::iso($notification->getCreatedAt()),
        ];
    }

    public function presentAll(array $notifications): array
    {
        return array_map(fn (UserNotification $notification): array => $this->present($notification), $notifications);
    }
}