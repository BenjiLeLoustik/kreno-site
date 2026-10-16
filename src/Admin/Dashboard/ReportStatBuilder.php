<?php

namespace App\Admin\Dashboard;

use App\Report\Presenter\ReportPresenter;
use App\Repository\Report\ReportRepository;
use DateTimeImmutable;

class ReportStatBuilder extends AbstractStatBuilder
{
    public const OPEN_REPORTS_SCAN = 200;

    public function __construct(
        private ReportRepository $reports,
        private ReportPresenter $presenter,
    ) {
    }

    public function getKey(): string
    {
        return 'reports';
    }

    public function getLabel(): string
    {
        return 'Signalements ouverts';
    }

    public function getRoute(): string
    {
        return 'admin_reports';
    }

    public function getRole(): string
    {
        return 'ROLE_MODERATOR';
    }

    public function getPosition(): int
    {
        return 20;
    }

    public function build(DateTimeImmutable $now): DashboardStat
    {
        $rows = $this->presenter->forAdminList($this->reports->findForAdmin(ReportRepository::OPEN_STATUSES, null, self::OPEN_REPORTS_SCAN));
        $high = count(array_filter($rows, static fn (array $row): bool => $row['priority'] === 'high'));

        return new DashboardStat(
            $this->reports->countOpen(),
            $high > 0 ? sprintf('%d %s', $high, $this->plural($high, 'priorité haute', 'priorités hautes')) : 'aucune priorité haute',
            $high > 0,
        );
    }
}