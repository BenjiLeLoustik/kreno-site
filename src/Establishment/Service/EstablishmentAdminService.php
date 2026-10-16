<?php

namespace App\Establishment\Service;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Event\EstablishmentResumedEvent;
use App\Establishment\Event\EstablishmentSuspendedEvent;
use App\Establishment\Exception\ResumeNotAllowedException;
use App\Establishment\Exception\SuspensionNotAllowedException;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class EstablishmentAdminService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private EventManagerInterface $events,
        private ActivityLogger $logger,
        private SlotPublicationService $publication,
    ) {
    }

    public function suspend(CompanyEstablishment $establishment, User $admin, string $reason): CompanyEstablishment
    {
        if ($establishment->getStatus() !== EstablishmentStatus::ONLINE) {
            throw new SuspensionNotAllowedException();
        }

        $reason = trim($reason);

        $this->orm->transactional(function () use ($establishment, $admin, $reason): void {
            $establishment->setStatus(EstablishmentStatus::PAUSED);
            $withdrawn = $this->publication->withdrawAll($establishment, $admin);
            $message = sprintf('Établissement « %s » suspendu', $establishment->getName());
            $context = [
                'reason' => $reason,
                'withdrawnSlots' => $withdrawn
            ];

            $this->logger->log(ActivityType::MODERATION, $message, $admin, null, $establishment, $context);
            $this->orm->flush();
        });

        $this->events->dispatch(new EstablishmentSuspendedEvent($establishment, $reason));

        return $establishment;
    }

    public function resume(CompanyEstablishment $establishment, User $admin): CompanyEstablishment
    {
        if ($establishment->getStatus() !== EstablishmentStatus::PAUSED) {
            throw new ResumeNotAllowedException();
        }

        $establishment->setStatus(EstablishmentStatus::ONLINE);

        $message = sprintf('Établissement « %s » remis en ligne', $establishment->getName());
        $this->logger->log(ActivityType::MODERATION, $message, $admin, null, $establishment);
        $this->orm->flush();

        $this->events->dispatch(new EstablishmentResumedEvent($establishment));

        return $establishment;
    }
}