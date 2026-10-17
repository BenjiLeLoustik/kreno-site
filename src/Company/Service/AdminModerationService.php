<?php

namespace App\Company\Service;

use App\Company\Dto\ModerationInput;
use App\Company\Exception\EstablishmentNotPendingException;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentStatus;

class AdminModerationService
{
    public function __construct(
        private ModerationService $moderation,
    ) {
    }

    public function approve(CompanyEstablishment $establishment, User $admin): void
    {
        $this->assertPending($establishment);
        $this->moderation->approve($establishment, $admin);
    }

    public function reject(CompanyEstablishment $establishment, ModerationInput $input, User $admin): void
    {
        $this->assertPending($establishment);
        $this->moderation->reject($establishment, $input, $admin);
    }

    public function requestDocument(CompanyEstablishment $establishment, ModerationInput $input, User $admin): void
    {
        $this->assertPending($establishment);
        $this->moderation->requestDocument($establishment, $input, $admin);
    }

    private function assertPending(CompanyEstablishment $establishment): void
    {
        if ($establishment->getStatus() !== EstablishmentStatus::PENDING_REVIEW) {
            throw new EstablishmentNotPendingException($establishment->getName());
        }
    }
}