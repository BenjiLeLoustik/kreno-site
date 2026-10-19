<?php

namespace App\Admin\Dashboard;

use App\Report\Presenter\ReportPresenter;
use App\Repository\Report\ReportRepository;
use App\Shared\Helper\Translation;
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
        return Translation::translate('Signalements ouverts');
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
        $high = $this->countHighPriority();

        return new DashboardStat(
            $this->reports->countOpen(),
            $this->note($high),
            $high > 0,
        );
    }

    private function countHighPriority(): int
    {
        $rows = $this->presenter->forAdminList(
            $this->reports->findForAdmin(
                ReportRepository::OPEN_STATUSES,
                null,
                self::OPEN_REPORTS_SCAN
            )
        );

        return count(array_filter($rows, static fn (array $row): bool => $row['priority'] === 'high'));
    }

    private function note(int $high): string
    {
        return Translation::translate(
            '{count, plural, =0 {aucune priorité haute} one {# priorité haute} other {# priorités hautes}}',
            [
                'count' => $high
            ],
        );
    }
}