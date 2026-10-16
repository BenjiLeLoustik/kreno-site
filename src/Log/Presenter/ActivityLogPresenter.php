<?php

namespace App\Log\Presenter;

use App\Billing\Helper\PeriodHelper;
use App\Entity\ActivityLog;
use App\Entity\User\User;
use App\Log\Enum\ActivityGroup;
use App\Shared\Helper\DateHelper;
use App\User\Helper\StaffRoleHelper;
use App\User\Helper\UserNameHelper;

class ActivityLogPresenter
{
    public function present(ActivityLog $log): array
    {
        $actor = $log->getActor();
        $type = $log->getType();
        $group = ActivityGroup::of($type);

        return [
            'id' => $log->getId(),
            'createdAt' => DateHelper::iso($log->getCreatedAt()),
            'when' => $this->when($log),
            'type' => $type?->value,
            'typeLabel' => $type?->label(),
            'group' => $group->value,
            'action' => $group->badge(),
            'message' => $log->getMessage(),
            'target' => $log->getMessage(),
            'who' => StaffRoleHelper::signature($actor),
            'actor' => $this->actor($actor),
            'version' => $log->getWebsite()?->getCode(),
            'subject' => $this->subject($log),
            'changes' => $log->getChanges(),
        ];
    }

    public function presentAll(array $logs): array
    {
        return array_map(fn (ActivityLog $log): array => $this->present($log), $logs);
    }

    private function when(ActivityLog $log): ?string
    {
        $createdAt = $log->getCreatedAt();

        if ($createdAt === null) {
            return null;
        }

        return sprintf('%s %s', PeriodHelper::shortDay($createdAt), $createdAt->format('H:i'));
    }

    private function actor(?User $actor): ?array
    {
        if ($actor === null) {
            return null;
        }

        return [
            'id' => $actor->getId(),
            'name' => UserNameHelper::shortName($actor),
        ];
    }

    private function subject(ActivityLog $log): ?array
    {
        if ($log->getSubjectType() === null) {
            return null;
        }

        return [
            'type' => $log->getSubjectType(),
            'id' => $log->getSubjectId()
        ];
    }
}