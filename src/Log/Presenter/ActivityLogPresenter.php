<?php

namespace App\Log\Presenter;

use App\Billing\Helper\PeriodHelper;
use App\Entity\ActivityLog;
use App\Log\Helper\ActivityGroupHelper;
use App\Shared\Helper\DateHelper;
use App\User\Helper\StaffRoleHelper;
use App\User\Helper\UserNameHelper;

class ActivityLogPresenter
{
    public function present(ActivityLog $log): array
    {
        $actor = $log->getActor();
        $type = $log->getType();
        $group = $type !== null ? ActivityGroupHelper::group($type) : 'settings';

        return [
            'id' => $log->getId(),
            'createdAt' => DateHelper::iso($log->getCreatedAt()),
            'when' => $log->getCreatedAt() !== null ? sprintf('%s %s', PeriodHelper::shortDay($log->getCreatedAt()), $log->getCreatedAt()->format('H:i')) : null,
            'type' => $type?->value,
            'typeLabel' => $type?->label(),
            'group' => $group,
            'action' => ActivityGroupHelper::BADGES[$group],
            'message' => $log->getMessage(),
            'target' => $log->getMessage(),
            'who' => StaffRoleHelper::signature($actor),
            'actor' => $actor === null ? null : [
                'id' => $actor->getId(),
                'name' => UserNameHelper::shortName($actor),
            ],
            'version' => $log->getWebsite()?->getCode(),
            'subject' => $log->getSubjectType() === null ? null : ['type' => $log->getSubjectType(), 'id' => $log->getSubjectId()],
            'changes' => $log->getChanges(),
        ];
    }

    public function presentAll(array $logs): array
    {
        return array_map(fn (ActivityLog $log): array => $this->present($log), $logs);
    }
}