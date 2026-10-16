<?php

namespace App\Company\Service;

use App\Company\Dto\ModerationInput;
use App\Company\Enum\CompanyStatus;
use App\Company\Enum\RejectionReason;
use App\Company\Event\DocumentRequestedEvent;
use App\Company\Event\EstablishmentApprovedEvent;
use App\Company\Event\EstablishmentRejectedEvent;
use App\Company\Exception\EstablishmentNotPendingException;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentStatus;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class ModerationService
{
    public const DEFAULT_DOCUMENT_REQUEST = 'Merci de nous transmettre un extrait Kbis ou un avis de situation INSEE de moins de 3 mois.';

    public function __construct(
        private OrmManagerInterface $orm,
        private ActivityLogger $logger,
        private EventManagerInterface $events,
    ) {
    }

    public function approve(CompanyEstablishment $establishment, User $admin): CompanyEstablishment
    {
        $this->assertPending($establishment);

        $now = new DateTimeImmutable();
        $establishment->setStatus(EstablishmentStatus::ONLINE)
            ->setVerifiedAt($now)
            ->setRejectionReason(null);

        $company = $establishment->getCompany();

        if ($company !== null && $company->getStatus() !== CompanyStatus::ACTIVE) {
            $company->setStatus(CompanyStatus::ACTIVE)
                ->setVerifiedAt($now)
                ->setRejectionReason(null);
        }

        $message = sprintf('%s validé', $establishment->getName());
        $this->logger->log(ActivityType::MODERATION, $message, $admin, null, $establishment);
        $this->orm->flush();

        $this->events->dispatch(new EstablishmentApprovedEvent($establishment));

        return $establishment;
    }

    public function reject(
        CompanyEstablishment $establishment,
        ModerationInput $input,
        User $admin,
    ): CompanyEstablishment {
        $this->assertPending($establishment);

        $reason = $this->reason($input->reason)->label();
        $establishment
            ->setStatus(EstablishmentStatus::REJECTED)
            ->setRejectionReason($this->rejectionText($reason, $input->message));

        $company = $establishment->getCompany();

        if ($company !== null && $company->getStatus() !== CompanyStatus::ACTIVE) {
            $company->setStatus(CompanyStatus::REJECTED)->setRejectionReason($reason);
        }

        $message = sprintf('%s refusé (%s)', $establishment->getName(), mb_strtolower($reason));
        $this->logger->log(ActivityType::MODERATION, $message, $admin, null, $establishment);
        $this->orm->flush();

        $this->events->dispatch(new EstablishmentRejectedEvent($establishment, $reason, $input->message));

        return $establishment;
    }

    public function requestDocument(
        CompanyEstablishment $establishment,
        ModerationInput $input,
        User $admin,
    ): CompanyEstablishment {
        $this->assertPending($establishment);

        $message = trim((string) $input->message) ?: self::DEFAULT_DOCUMENT_REQUEST;
        $log = sprintf('Justificatif demandé à %s', $establishment->getName());

        $this->logger->log(
            ActivityType::MODERATION,
            $log,
            $admin,
            null,
            $establishment,
            [
                'message' => $message
            ]
        );
        $this->orm->flush();

        $this->events->dispatch(new DocumentRequestedEvent($establishment, $message));

        return $establishment;
    }

    private function reason(?string $reason): RejectionReason
    {
        return RejectionReason::tryFrom((string) $reason) ?? RejectionReason::OTHER;
    }

    private function rejectionText(string $reason, ?string $message): string
    {
        if (!$message) {
            return $reason;
        }

        return $reason . ' : ' . $message;
    }

    private function assertPending(CompanyEstablishment $establishment): void
    {
        if ($establishment->getStatus() !== EstablishmentStatus::PENDING_REVIEW) {
            throw new EstablishmentNotPendingException((string) $establishment->getName());
        }
    }
}