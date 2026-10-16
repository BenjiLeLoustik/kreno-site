<?php

namespace App\Company\Service;

use App\Company\Dto\ModerationInput;
use App\Company\Enum\CompanyStatus;
use App\Company\Event\DocumentRequestedEvent;
use App\Company\Event\EstablishmentApprovedEvent;
use App\Company\Event\EstablishmentRejectedEvent;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentStatus;
use App\Log\Enum\ActivityType;
use App\Log\Service\ActivityLogger;
use App\Shared\Exception\BusinessRuleException;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class ModerationService
{
    public const REJECTION_REASONS = [
        'siret' => 'SIRET inactif ou introuvable',
        'address' => 'Adresse ne correspondant pas',
        'activity' => 'Activité non éligible',
        'other' => 'Autre',
    ];

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
        $establishment->setStatus(EstablishmentStatus::ONLINE)->setVerifiedAt($now)->setRejectionReason(null);

        $company = $establishment->getCompany();

        if ($company !== null && $company->getStatus() !== CompanyStatus::ACTIVE) {
            $company->setStatus(CompanyStatus::ACTIVE)->setVerifiedAt($now)->setRejectionReason(null);
        }

        $this->logger->log(ActivityType::MODERATION, sprintf('%s validé', $establishment->getName()), $admin, null, $establishment);
        $this->orm->flush();

        $this->events->dispatch(new EstablishmentApprovedEvent($establishment));

        return $establishment;
    }

    public function reject(CompanyEstablishment $establishment, ModerationInput $input, User $admin): CompanyEstablishment
    {
        $this->assertPending($establishment);

        $reason = self::REJECTION_REASONS[$input->reason ?? 'other'] ?? self::REJECTION_REASONS['other'];
        $establishment->setStatus(EstablishmentStatus::REJECTED)->setRejectionReason($reason . ($input->message ? ' : ' . $input->message : ''));

        $company = $establishment->getCompany();

        if ($company !== null && $company->getStatus() !== CompanyStatus::ACTIVE) {
            $company->setStatus(CompanyStatus::REJECTED)->setRejectionReason($reason);
        }

        $this->logger->log(ActivityType::MODERATION, sprintf('%s refusé (%s)', $establishment->getName(), mb_strtolower($reason)), $admin, null, $establishment);
        $this->orm->flush();

        $this->events->dispatch(new EstablishmentRejectedEvent($establishment, $reason, $input->message));

        return $establishment;
    }

    public function requestDocument(CompanyEstablishment $establishment, ModerationInput $input, User $admin): CompanyEstablishment
    {
        $this->assertPending($establishment);

        $message = trim((string) $input->message) ?: 'Merci de nous transmettre un extrait Kbis ou un avis de situation INSEE de moins de 3 mois.';

        $this->logger->log(ActivityType::MODERATION, sprintf('Justificatif demandé à %s', $establishment->getName()), $admin, null, $establishment, ['message' => $message]);
        $this->orm->flush();

        $this->events->dispatch(new DocumentRequestedEvent($establishment, $message));

        return $establishment;
    }

    private function assertPending(CompanyEstablishment $establishment): void
    {
        if ($establishment->getStatus() !== EstablishmentStatus::PENDING_REVIEW) {
            throw new BusinessRuleException('{name} n\'est pas en attente de vérification.', ['name' => $establishment->getName()]);
        }
    }
}