<?php

namespace App\Admin\Navbar;

use App\Establishment\Enum\EstablishmentStatus;
use App\Repository\Company\CompanyEstablishmentRepository;

class ModerationBuilder extends AbstractNavbarBuilder
{
    public function __construct(
        private CompanyEstablishmentRepository $establishments,
    ) {
    }

    public function getKey(): string
    {
        return 'moderation';
    }

    public function getLabel(): string
    {
        return 'Modération des pros';
    }

    public function getRoute(): string
    {
        return 'admin_moderation_index';
    }

    public function getRole(): string
    {
        return 'ROLE_MODERATOR';
    }

    public function getIcon(): string
    {
        return 'shield-check';
    }

    public function getPosition(): int
    {
        return 20;
    }

    public function getBadge(): int
    {
        return $this->establishments->countByStatus(EstablishmentStatus::PENDING_REVIEW);
    }
}