<?php

namespace App\Company\Navbar;

use App\Company\Security\Permission;
use App\Entity\Company\CompanyEstablishment;
use App\Repository\Establishment\EstablishmentReviewRepository;
use App\Shared\Helper\Translation;

class ReviewBuilder extends AbstractProNavbarBuilder
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
        return Translation::translate('Avis clients');
    }

    public function getRoute(): string
    {
        return 'pro_reviews';
    }

    public function getIcon(): string
    {
        return 'star';
    }

    public function getPosition(): int
    {
        return 65;
    }

    public function getPermission(): ?string
    {
        return Permission::PLANNING_VIEW;
    }

    public function getBadge(CompanyEstablishment $establishment): int
    {
        return $this->reviews->countUnanswered($establishment);
    }
}