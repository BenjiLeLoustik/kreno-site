<?php

namespace App\Review\Provider;

use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Entity\Establishment\EstablishmentReview;
use App\Repository\Establishment\EstablishmentReviewRepository;
use App\Review\Enum\ProReviewTab;
use App\Review\Exception\ProReviewNotFoundException;
use App\Review\Presenter\ReviewPresenter;

class ProReviewPageProvider
{
    public function __construct(
        private EstablishmentReviewRepository $reviews,
        private ReviewSummaryProvider $summaries,
        private ReviewPresenter $presenter,
        private PermissionResolver $resolver,
    ) {
    }

    public function index(CompanyMember $member, CompanyEstablishment $establishment, ProReviewTab $tab): array
    {
        $items = $this->reviews->findForProfessional($establishment, $tab->criteria());

        return [
            'tab' => $tab->value,
            'tabs' => $this->tabs($tab),
            'summary' => $this->summaries->summary($establishment),
            'reviews' => $this->presenter->professionalList($items),
            'canManage' => $this->resolver->memberCan($member, Permission::ESTABLISHMENT_EDIT, $establishment),
        ];
    }

    public function owned(CompanyEstablishment $establishment, int $id): EstablishmentReview
    {
        $review = $this->reviews->find($id);

        if (!$review instanceof EstablishmentReview || $review->getEstablishment() !== $establishment) {
            throw new ProReviewNotFoundException($id);
        }

        return $review;
    }

    private function tabs(ProReviewTab $selected): array
    {
        $tabs = [];

        foreach (ProReviewTab::cases() as $tab) {
            $tabs[] = [
                'key' => $tab->value,
                'label' => $tab->label(),
                'selected' => $tab === $selected
            ];
        }

        return $tabs;
    }
}