<?php

namespace App\Admin\Dashboard;

use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Helper\CalendarHelper;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\Shared\Helper\Translation;
use DateTimeImmutable;

class ProStatBuilder extends AbstractStatBuilder
{
    public function __construct(
        private CompanyEstablishmentRepository $establishments,
    ) {
    }

    public function getKey(): string
    {
        return 'pros';
    }

    public function getLabel(): string
    {
        return Translation::translate('Pros actifs');
    }

    public function getRoute(): string
    {
        return 'admin_establishments';
    }

    public function getRole(): string
    {
        return 'ROLE_MODERATOR';
    }

    public function getPosition(): int
    {
        return 40;
    }

    public function build(DateTimeImmutable $now): DashboardStat
    {
        return new DashboardStat(
            $this->establishments->countByStatus(EstablishmentStatus::ONLINE),
            sprintf(
                Translation::translate('+%d cette semaine'),
                $this->establishments->countVerifiedSince(CalendarHelper::mondayOf($now)->setTime(0, 0))
            ),
        );
    }
}