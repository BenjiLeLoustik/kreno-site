<?php

namespace App\Establishment\Service;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Event\EstablishmentResumedEvent;
use App\Establishment\Event\EstablishmentSuspendedEvent;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Shared\Exception\BusinessRuleException;
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
            throw new BusinessRuleException('Seul un établissement en ligne peut être suspendu.');
        }

        $reason = trim($reason);

        $this->orm->transactional(function () use ($establishment, $admin, $reason): void {
            $establishment->setStatus(EstablishmentStatus::PAUSED);
            $withdrawn = $this->publication->withdrawAll($establishment, $admin);
            $this->logger->log(ActivityType::MODERATION, sprintf('Établissement « %s » suspendu', $establishment->getName()), $admin, null, $establishment, ['reason' => $reason, 'withdrawnSlots' => $withdrawn]);
            $this->orm->flush();
        });

        $this->events->dispatch(new EstablishmentSuspendedEvent($establishment, $reason));

        return $establishment;
    }

    public function resume(CompanyEstablishment $establishment, User $admin): CompanyEstablishment
    {
        if ($establishment->getStatus() !== EstablishmentStatus::PAUSED) {
            throw new BusinessRuleException('Seul un établissement suspendu peut être remis en ligne.');
        }

        $establishment->setStatus(EstablishmentStatus::ONLINE);
        $this->logger->log(ActivityType::MODERATION, sprintf('Établissement « %s » remis en ligne', $establishment->getName()), $admin, null, $establishment);
        $this->orm->flush();

        $this->events->dispatch(new EstablishmentResumedEvent($establishment));

        return $establishment;
    }
}