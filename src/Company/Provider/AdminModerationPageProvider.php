<?php

namespace App\Company\Provider;

use App\Company\Enum\RejectionReason;
use App\Company\Presenter\EstablishmentSummaryPresenter;
use App\Entity\Company\CompanyEstablishment;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Exception\EstablishmentNotFoundException;
use App\Repository\Company\CompanyEstablishmentRepository;

class AdminModerationPageProvider
{
    public function __construct(
        private CompanyEstablishmentRepository $establishments,
        private EstablishmentSummaryPresenter $presenter,
    ) {
    }

    public function index(?CompanyEstablishment $selected = null): array
    {
        $pending = $this->establishments->findByStatus(EstablishmentStatus::PENDING_REVIEW);
        $recent = $this->establishments->findRecentlyModerated();

        return [
            'pending' => array_map($this->presenter->forModeration(...), $pending),
            'recent' => array_map($this->presenter->forModeration(...), $recent),
            'selected' => $this->detail($selected ?? $pending[0] ?? $recent[0] ?? null),
            'rejectionReasons' => $this->rejectionReasons(),
        ];
    }

    public function establishment(int $id): CompanyEstablishment
    {
        $establishment = $this->establishments->find($id);

        if (!$establishment instanceof CompanyEstablishment) {
            throw new EstablishmentNotFoundException();
        }

        return $establishment;
    }

    private function detail(?CompanyEstablishment $establishment): ?array
    {
        if ($establishment === null) {
            return null;
        }

        return $this->presenter->forModeration($establishment);
    }

    private function rejectionReasons(): array
    {
        $reasons = [];

        foreach (RejectionReason::cases() as $reason) {
            $reasons[] = [
                'value' => $reason->value,
                'label' => $reason->label()
            ];
        }

        return $reasons;
    }
}