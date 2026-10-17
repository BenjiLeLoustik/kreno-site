<?php

namespace App\User\Navbar;

use App\Entity\User\User;
use App\Review\Provider\CustomerReviewPageProvider;

class ReviewBuilder extends AbstractAccountNavbarBuilder
{
    public function __construct(
        private CustomerReviewPageProvider $reviews,
    ) {
    }

    public function getKey(): string
    {
        return 'reviews';
    }

    public function getLabel(): string
    {
        return 'Mes avis';
    }

    public function getRoute(): string
    {
        return 'account_reviews';
    }

    public function getIcon(): string
    {
        return 'star';
    }

    public function getPosition(): int
    {
        return 30;
    }

    public function getBadge(User $user): int
    {
        return $this->reviews->pendingCount($user);
    }
}