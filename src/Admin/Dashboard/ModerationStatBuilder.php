<?php

namespace App\Admin\Dashboard;

use App\Establishment\Enum\EstablishmentStatus;
use App\Repository\Company\CompanyEstablishmentRepository;
use DateTimeImmutable;

class ModerationStatBuilder extends AbstractStatBuilder
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
        return 'Pros à vérifier';
    }

    public function getRoute(): string
    {
        return 'admin_moderation_index';
    }

    public function getRole(): string
    {
        return 'ROLE_MODERATOR';
    }

    public function getPosition(): int
    {
        return 10;
    }

    public function build(DateTimeImmutable $now): DashboardStat
    {
        $count = $this->establishments->countByStatus(EstablishmentStatus::PENDING_REVIEW);
        $oldest = $this->establishments->findByStatus(EstablishmentStatus::PENDING_REVIEW, 1)[0] ?? null;
        $since = $oldest?->getUpdatedAt();

        if ($since !== null) {
            $sinceText = sprintf('plus ancien : %s', $this->age($since, $now));
        } else {
            $sinceText = 'rien en attente';
        }

        return new DashboardStat($count, $sinceText, $count > 0);
    }

    private function age(DateTimeImmutable $since, DateTimeImmutable $now): string
    {
        $hours = intdiv(max(0, $now->getTimestamp() - $since->getTimestamp()), 3600);

        if ($hours < 24) {
            if ($hours < 1) {
                return 'moins d\'1 h';
            }
            return sprintf('%d h', $hours);
        }

        $days = intdiv($hours, 24);

        return sprintf(
            '%d %s',
            $days,
            $this->plural($days, 'jour', 'jours')
        );
    }
}