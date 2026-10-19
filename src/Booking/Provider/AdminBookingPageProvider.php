<?php

namespace App\Booking\Provider;

use App\Booking\Enum\AdminBookingTab;
use App\Booking\Enum\AdminBookingText;
use App\Booking\Exception\BookingNotFoundException;
use App\Booking\Presenter\BookingPresenter;
use App\Entity\Booking;
use App\Repository\BookingRepository;
use DateTimeImmutable;

class AdminBookingPageProvider
{
    public const PAGE_SIZE = 50;

    public function __construct(
        private BookingRepository $bookings,
        private BookingPresenter $presenter,
    ) {
    }

    public function index(AdminBookingTab $tab, string $search, int $page): array
    {
        $now = new DateTimeImmutable();
        $bookings = $this->bookings->findForAdmin(
            $tab->statuses(),
            $this->search($search),
            self::PAGE_SIZE + 1,
            ($page - 1) * self::PAGE_SIZE,
        );

        return [
            'tab' => $tab->value,
            'tabs' => AdminBookingTab::choices(),
            'q' => $search,
            'page' => $page,
            'hasNext' => count($bookings) > self::PAGE_SIZE,
            'today' => $this->today($now),
            'bookings' => $this->presenter->forAdminList(array_slice($bookings, 0, self::PAGE_SIZE), $now),
        ];
    }

    public function show(int $id): array
    {
        return ['booking' => $this->presenter->forAdminDetail($this->booking($id))];
    }

    public function booking(int $id): Booking
    {
        $booking = $this->bookings->find($id);

        if (!$booking instanceof Booking) {
            throw new BookingNotFoundException();
        }

        return $booking;
    }

    private function search(string $search): ?string
    {
        if ($search === '') {
            return null;
        }

        return $search;
    }

    private function today(DateTimeImmutable $now): string
    {
        $today = $now->setTime(0, 0);
        $tomorrow = $today->modify('+1 day');

        return AdminBookingText::today(
            $this->bookings->countCreatedBetween($today, $tomorrow),
            $this->bookings->countCancelledBetween($today, $tomorrow),
            $this->bookings->countNoShowsBetween($today, $tomorrow),
        );
    }
}