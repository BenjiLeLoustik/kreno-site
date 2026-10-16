<?php

namespace App\Log\Service;

use App\Entity\AppLog;
use App\Entity\User\User;
use App\Enum\Log\AppLogStatus;
use App\Repository\AppLogRepository;
use DateTimeImmutable;
use NeoPHP\Package\Orm\OrmManagerInterface;

class AppLogService
{
    public const RETENTION_DAYS = 30;

    public function __construct(
        private OrmManagerInterface $orm,
        private AppLogRepository $logs,
    ) {
    }

    public function changeStatus(AppLog $log, AppLogStatus $status, User $admin, ?string $note = null): AppLog
    {
        $log->setStatus($status)
            ->setHandledBy($status === AppLogStatus::NEW ? null : $admin)
            ->setHandledAt($status === AppLogStatus::NEW ? null : new DateTimeImmutable())
            ->setNote($note !== null && trim($note) !== '' ? mb_substr(trim($note), 0, 2000) : $log->getNote());

        $this->orm->flush();

        return $log;
    }

    public function delete(AppLog $log): void
    {
        $this->orm->remove($log);
        $this->orm->flush();
    }

    public function purge(int $days = self::RETENTION_DAYS): int
    {
        $logs = $this->logs->findHandledBefore(new DateTimeImmutable(sprintf('-%d days', $days)));

        foreach ($logs as $log) {
            $this->orm->remove($log);
        }

        $this->orm->flush();

        return count($logs);
    }
}