<?php

namespace App\Admin\Navbar;

use App\Repository\Establishment\EstablishmentReviewRepository;
use App\Review\Enum\ReviewStatus;
use App\Shared\Helper\Translation;

class ReviewBuilder extends AbstractNavbarBuilder
{
    public function __construct(
        private EstablishmentReviewRepository $reviews,
    ) {
    }

    public function getKey(): string
    {
        return 'reviews';
    }

    public function getLabel(): string
    {
        return Translation::translate('Avis');
    }

    public function getRoute(): string
    {
        return 'admin_reviews';
    }

    public function getRole(): string
    {
        return 'ROLE_MODERATOR';
    }

    public function getIcon(): string
    {
        return 'star';
    }

    public function getPosition(): int
    {
        return 65;
    }

    public function getBadge(): int
    {
        return $this->reviews->countByStatus(ReviewStatus::REPORTED);
    }
}