<?php

namespace App\Review\Provider;

use App\Entity\Establishment\EstablishmentReview;
use App\Repository\Establishment\EstablishmentReviewRepository;
use App\Review\Enum\AdminReviewFilter;
use App\Review\Enum\ReviewStatus;
use App\Review\Exception\ReviewNotFoundException;
use App\Review\Presenter\ReviewPresenter;

class AdminReviewPageProvider
{
    public const LIMIT = 100;

    public function __construct(
        private EstablishmentReviewRepository $reviews,
        private ReviewPresenter $presenter,
    ) {
    }

    public function index(AdminReviewFilter $filter): array
    {
        $items = $this->reviews->findForAdmin($filter->statuses(), self::LIMIT);

        return [
            'filter' => $filter->value,
            'filters' => $this->filters($filter),
            'rows' => $this->presenter->adminList($items),
        ];
    }

    public function review(int $id): EstablishmentReview
    {
        $review = $this->reviews->find($id);

        if (!$review instanceof EstablishmentReview) {
            throw new ReviewNotFoundException($id);
        }

        return $review;
    }

    private function filters(AdminReviewFilter $selected): array
    {
        $filters = [];

        foreach (AdminReviewFilter::cases() as $filter) {
            $filters[] = [
                'key' => $filter->value,
                'label' => $filter->label(),
                'count' => $this->count($filter),
                'selected' => $filter === $selected,
            ];
        }

        return $filters;
    }

    private function count(AdminReviewFilter $filter): ?int
    {
        if ($filter === AdminReviewFilter::ALL) {
            return null;
        }

        $status = $filter->statuses()[0];

        if (!$status instanceof ReviewStatus) {
            return null;
        }

        return $this->reviews->countByStatus($status);
    }
}