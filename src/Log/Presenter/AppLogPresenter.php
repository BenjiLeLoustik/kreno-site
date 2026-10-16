<?php

namespace App\Log\Presenter;

use App\Entity\AppLog;
use App\User\Helper\UserNameHelper;

class AppLogPresenter
{
    public function row(AppLog $log): array
    {
        $class = (string) $log->getExceptionClass();

        return [
            'id' => $log->getId(),
            'shortClass' => substr($class, (int) strrpos('\\' . $class, '\\')),
            'message' => mb_strimwidth((string) $log->getMessage(), 0, 140, '…'),
            'status' => $log->getStatus()?->value,
            'statusLabel' => $log->getStatus()?->label(),
            'tone' => $log->getStatus()?->tone() ?? 'info',
            'occurrences' => $log->getOccurrences(),
            'lastSeenAt' => $log->getLastSeenAt(),
        ];
    }

    public function detail(AppLog $log): array
    {
        $user = $log->getUser();
        $handledBy = $log->getHandledBy();

        return $this->row($log) + [
                'exceptionClass' => $log->getExceptionClass(),
                'fullMessage' => $log->getMessage(),
                'level' => $log->getLevel()?->label(),
                'location' => $log->getFile() !== null ? sprintf('%s:%d', $log->getFile(), (int) $log->getLine()) : null,
                'trace' => $log->getTrace(),
                'statusCode' => $log->getStatusCode(),
                'request' => trim(sprintf('%s %s', $log->getMethod(), $log->getUrl())),
                'user' => $user === null ? null : ['id' => $user->getId(), 'name' => UserNameHelper::fullName($user), 'email' => $user->getEmail()],
                'firstSeenAt' => $log->getFirstSeenAt(),
                'handledBy' => $handledBy !== null ? UserNameHelper::fullName($handledBy) : null,
                'handledAt' => $log->getHandledAt(),
                'note' => $log->getNote(),
            ];
    }
}