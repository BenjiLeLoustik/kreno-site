<?php

namespace App\Log\Presenter;

use App\Entity\AppLog;
use App\Entity\User\User;
use App\User\Helper\UserNameHelper;

class AppLogPresenter
{
    public const MESSAGE_LENGTH = 140;

    public function row(AppLog $log): array
    {
        return [
            'id' => $log->getId(),
            'shortClass' => $this->shortClass((string) $log->getExceptionClass()),
            'message' => mb_strimwidth((string) $log->getMessage(), 0, self::MESSAGE_LENGTH, '…'),
            'status' => $log->getStatus()?->value,
            'statusLabel' => $log->getStatus()?->label(),
            'tone' => $log->getStatus()?->tone() ?? 'info',
            'occurrences' => $log->getOccurrences(),
            'lastSeenAt' => $log->getLastSeenAt(),
        ];
    }

    public function detail(AppLog $log): array
    {
        return $this->row($log) + [
                'exceptionClass' => $log->getExceptionClass(),
                'fullMessage' => $log->getMessage(),
                'level' => $log->getLevel()?->label(),
                'location' => $this->location($log),
                'trace' => $log->getTrace(),
                'statusCode' => $log->getStatusCode(),
                'request' => trim(sprintf('%s %s', $log->getMethod(), $log->getUrl())),
                'user' => $this->user($log->getUser()),
                'firstSeenAt' => $log->getFirstSeenAt(),
                'handledBy' => $this->handledBy($log->getHandledBy()),
                'handledAt' => $log->getHandledAt(),
                'note' => $log->getNote(),
            ];
    }

    private function shortClass(string $class): string
    {
        return substr($class, (int) strrpos('\\' . $class, '\\'));
    }

    private function location(AppLog $log): ?string
    {
        if ($log->getFile() === null) {
            return null;
        }

        return sprintf('%s:%d', $log->getFile(), (int) $log->getLine());
    }

    private function user(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->getId(),
            'name' => UserNameHelper::fullName($user),
            'email' => $user->getEmail(),
        ];
    }

    private function handledBy(?User $handledBy): ?string
    {
        if ($handledBy === null) {
            return null;
        }

        return UserNameHelper::fullName($handledBy);
    }
}