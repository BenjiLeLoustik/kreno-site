<?php

namespace App\Review\Provider;

use App\Entity\Company\CompanyEstablishment;
use App\Repository\Establishment\EstablishmentReviewRepository;
use App\Review\Presenter\ReviewPresenter;

class ReviewSummaryProvider
{
    public const PUBLIC_LIMIT = 10;

    public function __construct(
        private EstablishmentReviewRepository $reviews,
        private ReviewPresenter $presenter,
    ) {
    }

    public function forEstablishment(CompanyEstablishment $establishment): array
    {
        $items = $this->reviews->findVisibleForEstablishment($establishment, self::PUBLIC_LIMIT);

        return $this->summary($establishment) + [
                'items' => $this->presenter->publicList($items),
            ];
    }

    public function summary(CompanyEstablishment $establishment): array
    {
        return $this->presenter->summary($this->reviews->ratingDistribution($establishment));
    }
}