<?php

namespace App\Admin\Provider;

use App\Company\Presenter\EstablishmentSummaryPresenter;
use App\Entity\Company\CompanyEstablishment;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Helper\CalendarHelper;
use App\Report\Presenter\ReportPresenter;
use App\Repository\BookingRepository;
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
        'low' => 2
    ];

    public function __construct(
        private CompanyEstablishmentRepository $establishments,
        private ReportRepository $reports,
        private BookingRepository $bookings,
        private EstablishmentSummaryPresenter $establishmentPresenter,
        private ReportPresenter $reportPresenter,
    ) {
    }

    public function build(?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $pending = $this->pending($now);
        $reports = $this->openReports();
        $today = $now->setTime(0, 0);
        $tomorrow = $today->modify('+1 day');
        $bookingsToday = $this->bookings->countCreatedBetween($today, $tomorrow);
        $cancellationsToday = $this->bookings->countCancelledBetween($today, $tomorrow);
        $online = $this->establishments->countByStatus(EstablishmentStatus::ONLINE);
        $newThisWeek = $this->establishments->countVerifiedSince(
            CalendarHelper::mondayOf($now)->setTime(0, 0)
        );

        return [
            'stats' => [
                [
                    'key' => 'moderation',
                    'label' => 'Pros à vérifier',
                    'value' => $pending['count'],
                    'note' => $pending['oldest'] !== null
                        ? sprintf('plus ancien : %s', $this->age($pending['oldest'], $now))
                        : 'rien en attente',
                    'alert' => $pending['count'] > 0,
                    'route' => 'admin_moderation_index',
                ],
                [
                    'key' => 'reports',
                    'label' => 'Signalements ouverts',
                    'value' => $reports['count'],
                    'note' => $reports['high'] > 0
                        ? sprintf('%d priorité%s haute%s', $reports['high'], $reports['high'] > 1
                            ? 's'
                            : '', $reports['high'] > 1 ? 's' : '') : 'aucune priorité haute',
                    'alert' => $reports['high'] > 0,
                    'route' => 'admin_reports',
                ],
                [
                    'key' => 'bookings',
                    'label' => 'Réservations aujourd’hui',
                    'value' => $bookingsToday,
                    'note' => sprintf('%d annulation%s', $cancellationsToday, $cancellationsToday > 1 ? 's' : ''),
                    'alert' => false,
                    'route' => 'admin_bookings',
                ],
                [
                    'key' => 'pros',
                    'label' => 'Pros actifs',
                    'value' => $online,
                    'note' => sprintf('+%d cette semaine', $newThisWeek),
                    'alert' => false,
                    'route' => 'admin_establishments',
                ],
            ],
            'pending' => $pending['items'],
            'reports' => $reports['items'],
        ];
    }

    private function pending(DateTimeImmutable $now): array
    {
        $establishments = $this->establishments->findByStatus(EstablishmentStatus::PENDING_REVIEW, self::LIST_SIZE);
        $oldest = $establishments !== [] ? $establishments[0]->getUpdatedAt() : null;

        $items = array_map(fn (CompanyEstablishment $establishment): array =>
            $this->establishmentPresenter->summary($establishment) +
            [
                'when' => $establishment->getUpdatedAt() !== null
                    ? CalendarHelper::relative($establishment->getUpdatedAt(), $now)
                    : null,
            ],
        $establishments);

        return [
            'count' => $this->establishments->countByStatus(EstablishmentStatus::PENDING_REVIEW),
            'oldest' => $oldest,
            'items' => $items,
        ];
    }

    private function openReports(): array
    {
        $reports = $this->reports->findForAdmin(ReportRepository::OPEN_STATUSES, null, self::OPEN_REPORTS_SCAN);
        $rows = $this->reportPresenter->forAdminList($reports);
        $high = count(array_filter($rows, static fn (array $row): bool => $row['priority'] === 'high'));

        usort($rows, static fn (array $a, array $b): int =>
            self::PRIORITY_ORDER[$a['priority']] <=> self::PRIORITY_ORDER[$b['priority']]
        );

        return [
            'count' => $this->reports->countOpen(),
            'high' => $high,
            'items' => array_slice($rows, 0, self::LIST_SIZE),
        ];
    }

    private function age(DateTimeImmutable $since, DateTimeImmutable $now): string
    {
        $hours = intdiv(max(0, $now->getTimestamp() - $since->getTimestamp()), 3600);

        if ($hours < 24) {
            return $hours < 1 ? 'moins d’1 h' : sprintf('%d h', $hours);
        }

        $days = intdiv($hours, 24);

        return sprintf('%d jour%s', $days, $days > 1 ? 's' : '');
    }
}