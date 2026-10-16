<?php

namespace App\Log\Service;

use App\Entity\ActivityLog;
use App\Entity\User\User;
use App\Entity\Website\WebsiteConfig;
use App\Log\Enum\ActivityType;
use NeoPHP\Package\Orm\Contract\EntityManagerInterface;
use ReflectionClass;

class ActivityLogger
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function log(
        ActivityType $type,
        string $message,
        ?User $actor = null,
        ?WebsiteConfig $website = null,
        ?object $subject = null,
        array $changes = []
    ): ActivityLog {

        $log = new ActivityLog();
        $log->setType($type);
        $log->setMessage(mb_substr($message, 0, 255));
        $log->setActor($actor);
        $log->setWebsite($website);
        $log->setChanges($changes);

        if ($subject !== null && method_exists($subject, 'getId')) {
            $log->setSubjectType((new ReflectionClass($subject))->getShortName());
            $log->setSubjectId($subject->getId());
        }

        $this->entityManager->persist($log);

        return $log;
    }
}