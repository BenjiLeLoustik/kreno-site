<?php

namespace App\Report\Provider;

use App\Booking\Enum\BookingStatus;
use App\Entity\Booking;
use App\Entity\Report\Report;
use App\Entity\User\User;
use App\Report\Enum\ReportDirection;
use App\Report\Exception\ReportNotFoundException;
use App\Report\Helper\ReportReasonHelper;
use App\Report\Presenter\ReportPresenter;
use App\Report\Service\ReportService;
use App\Repository\BookingRepository;
use App\Repository\Report\ReportRepository;
use NeoPHP\Component\Session\SessionManagerInterface;

class CustomerReportProvider
{
    public const OLD_REPORT = 'account.report.old';

    public const BOOKING_LIMIT = 30;

    public const BOOKING_STATUSES = [
        BookingStatus::CONFIRMED,
        BookingStatus::HONORED,
        BookingStatus::NO_SHOW,
        BookingStatus::CANCELLED_BY_ESTABLISHMENT,
    ];

    public function __construct(
        private ReportService $reportService,
        private ReportRepository $reports,
        private BookingRepository $bookings,
        private ReportPresenter $presenter,
        private SessionManagerInterface $session,
    ) {
    }

    public function index(User $user, ?string $booking): array
    {
        $old = $this->pullOld();

        return [
            'form' => [
                'bookings' => $this->reportableBookings($user),
                'selected' => $old['booking'] ?? $booking,
                'reason' => $old['reason'] ?? null,
                'description' => $old['description'] ?? null,
                'reasons' => ReportReasonHelper::choices(ReportDirection::FROM_CUSTOMER, $old['reason'] ?? null),
                'windowHours' => ReportService::CUSTOMER_WINDOW_HOURS,
            ],
            'reports' => $this->presenter->forCustomerList($this->reports->findForReporter($user)),
        ];
    }

    public function show(int $id, User $user): array
    {
        return [
            'report' => $this->presenter->forCustomer($this->owned($id, $user)),
        ];
    }

    public function owned(int $id, User $user): Report
    {
        $report = $this->reports->findOneForReporter($user, $id);

        if ($report === null) {
            throw new ReportNotFoundException();
        }

        return $report;
    }

    public function remember(array $old): void
    {
        $this->session->set(self::OLD_REPORT, $old);
    }

    private function pullOld(): array
    {
        $old = $this->session->get(self::OLD_REPORT);
        $this->session->remove(self::OLD_REPORT);

        if (!is_array($old)) {
            return [];
        }

        return $old;
    }

    private function reportableBookings(User $user): array
    {
        $items = [];
        $bookings = $this->bookings->findForCustomer($user, self::BOOKING_STATUSES, null, null, self::BOOKING_LIMIT);

        foreach ($bookings as $booking) {
            if ($booking instanceof Booking && $this->reportService->canCustomerReport($booking)) {
                $items[] = $this->bookingItem($booking);
            }
        }

        return $items;
    }

    private function bookingItem(Booking $booking): array
    {
        return [
            'reference' => $booking->getReference(),
            'label' => $this->presenter->bookingLabel($booking),
            'establishment' => $this->establishmentLabel($booking),
        ];
    }

    private function establishmentLabel(Booking $booking): ?string
    {
        $establishment = $booking->getSlot()?->getEstablishment();

        if ($establishment === null) {
            return null;
        }

        return trim(sprintf('%s · %s', $establishment->getName(), $establishment->getLocality()), ' ·');
    }
}