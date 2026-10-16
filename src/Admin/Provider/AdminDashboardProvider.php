<?php

namespace App\Admin\Provider;

use App\Admin\Registry\StatRegistry;
use App\Company\Presenter\EstablishmentSummaryPresenter;
use App\Entity\Company\CompanyEstablishment;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Helper\CalendarHelper;
use App\Report\Presenter\ReportPresenter;
use App\Repository\Company\CompanyEstablishmentRepository;
use App\Repository\Report\ReportRepository;
use DateTimeImmutable;

class AdminDashboardProvider
{
    public const LIST_SIZE = 3;

    private const OPEN_REPORTS_SCAN = 200;

    private const PRIORITY_ORDER = [
        'high' => 0,
        'mid' => 1,
        'low' => 2,
    ];

    public function __construct(
        private StatRegistry $stats,
        private CompanyEstablishmentRepository $establishments,
        private ReportRepository $reports,
        private EstablishmentSummaryPresenter $establishmentPresenter,
        private ReportPresenter $reportPresenter,
    ) {
    }

    public function build(?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();

        return [
            'stats' => $this->stats->items($now),
            'pending' => $this->pending($now),
            'reports' => $this->openReports(),
        ];
    }

    private function pending(DateTimeImmutable $now): array
    {
        return array_map(
            fn (CompanyEstablishment $establishment): array => $this->establishmentPresenter->summary($establishment) + [
                'when' => $establishment->getUpdatedAt() !== null
                    ? CalendarHelper::relative($establishment->getUpdatedAt(), $now)
                    : null,
            ], $this->establishments->findByStatus(EstablishmentStatus::PENDING_REVIEW, self::LIST_SIZE)
        );
    }

    private function openReports(): array
    {
        $rows = $this->reportPresenter->forAdminList(
            $this->reports->findForAdmin(
                ReportRepository::OPEN_STATUSES,
                null,
                self::OPEN_REPORTS_SCAN
            )
        );

        usort($rows, static fn (array $a, array $b): int =>
            (self::PRIORITY_ORDER[$a['priority']] ?? 3) <=> (self::PRIORITY_ORDER[$b['priority']] ?? 3)
        );

        return array_slice($rows, 0, self::LIST_SIZE);
    }
}