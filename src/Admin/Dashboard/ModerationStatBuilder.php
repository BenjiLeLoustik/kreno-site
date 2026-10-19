<?php

namespace App\Admin\Dashboard;

use App\Establishment\Enum\EstablishmentStatus;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\Shared\Helper\Translation;
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
        return Translation::translate('Pros à vérifier');
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
            $sinceText = sprintf(
                Translation::translate('plus ancien : %s'),
                $this->age($since, $now)
            );
        } else {
            $sinceText = Translation::translate('rien en attente');
        }

        return new DashboardStat($count, $sinceText, $count > 0);
    }

    private function age(DateTimeImmutable $since, DateTimeImmutable $now): string
    {
        $hours = intdiv(max(0, $now->getTimestamp() - $since->getTimestamp()), 3600);

        if ($hours < 1) {
            return Translation::translate('moins d\'1 h');
        }

        if ($hours < 24) {
            return Translation::translate('{hours} h', ['hours' => $hours]);
        }

        return Translation::translate(
            '{count, plural, one {# jour} other {# jours}}',
            [
                'count' => intdiv($hours, 24)
            ],
        );
    }
}