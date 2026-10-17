<?php

namespace App\Log\Provider;

use App\Entity\AppLog;
use App\Log\Enum\AppLogStatus;
use App\Log\Exception\AppLogNotFoundException;
use App\Log\Presenter\AppLogPresenter;
use App\Log\Service\AppLogService;
use App\Repository\AppLogRepository;

class AppLogPageProvider
{
    public const PER_PAGE = 50;

    public const ALL_STATUSES = 'all';

    public function __construct(
        private AppLogRepository $logs,
        private AppLogPresenter $presenter,
    ) {
    }

    public function index(int $page, ?AppLogStatus $status, string $search, ?AppLog $selected = null): array
    {
        $logs = $this->logs->findForAdmin(
            $status,
            $this->search($search),
            self::PER_PAGE + 1,
            ($page - 1) * self::PER_PAGE,
        );
        $visible = array_slice($logs, 0, self::PER_PAGE);

        return [
            'filters' => ['q' => $search, 'status' => $status?->value ?? self::ALL_STATUSES, 'page' => $page],
            'hasNext' => count($logs) > self::PER_PAGE,
            'statuses' => $this->statuses(),
            'logs' => $this->rows($visible),
            'selected' => $this->detail($selected ?? $visible[0] ?? null),
            'retentionDays' => AppLogService::RETENTION_DAYS,
        ];
    }

    public function log(int $id): AppLog
    {
        $log = $this->logs->find($id);

        if (!$log instanceof AppLog) {
            throw new AppLogNotFoundException();
        }

        return $log;
    }

    private function search(string $search): ?string
    {
        if ($search === '') {
            return null;
        }

        return $search;
    }

    private function statuses(): array
    {
        $statuses = [];

        foreach (AppLogStatus::cases() as $status) {
            $statuses[] = [
                'value' => $status->value,
                'label' => $status->label(),
                'count' => $this->logs->countByStatus($status),
            ];
        }

        return $statuses;
    }

    private function rows(array $logs): array
    {
        $rows = [];

        foreach ($logs as $log) {
            $rows[] = $this->presenter->row($log);
        }

        return $rows;
    }

    private function detail(?AppLog $log): ?array
    {
        if ($log === null) {
            return null;
        }

        return $this->presenter->detail($log);
    }
}