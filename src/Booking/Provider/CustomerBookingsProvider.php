<?php

namespace App\Booking\Provider;

use App\Booking\Enum\BookingStatus;
use App\Booking\Enum\BookingTab;
use App\Booking\Exception\CustomerBookingNotFoundException;
use App\Booking\Presenter\BookingPresenter;
use App\Entity\Booking;
use App\Entity\User\User;
use App\Repository\BookingRepository;
use DateTimeImmutable;

class CustomerBookingsProvider
{
    public const PAST_STATUSES = [BookingStatus::CONFIRMED, BookingStatus::HONORED, BookingStatus::NO_SHOW];

    public function __construct(
        private BookingRepository $bookings,
        private BookingPresenter $presenter,
    ) {
    }

    public function index(User $user, string $tab): array
    {
        $selected = BookingTab::of($tab);
        $now = new DateTimeImmutable();

        return [
            'tab' => $selected->value,
            'tabs' => $this->tabs($selected),
            'bookings' => $this->presenter->forCustomerList($this->bookingsFor($user, $selected, $now), $now),
        ];
    }

    public function owned(string $reference, User $user): Booking
    {
        $booking = $this->bookings->findOneByReference($reference);

        if (!$booking instanceof Booking || $booking->getCustomer() !== $user) {
            throw new CustomerBookingNotFoundException();
        }

        return $booking;
    }

    private function bookingsFor(User $user, BookingTab $tab, DateTimeImmutable $now): array
    {
        return match ($tab) {
            BookingTab::UPCOMING => $this->bookings->findForCustomer($user, [BookingStatus::CONFIRMED], $now),
            BookingTab::PAST => $this->bookings->findForCustomer($user, self::PAST_STATUSES, null, $now),
            BookingTab::CANCELLED => $this->bookings->findForCustomer($user, BookingRepository::CANCELLED_STATUSES),
        };
    }

    private function tabs(BookingTab $selected): array
    {
        $tabs = [];

        foreach (BookingTab::cases() as $tab) {
            $tabs[] = [
                'key' => $tab->value,
                'label' => $tab->label(),
                'selected' => $tab === $selected
            ];
        }

        return $tabs;
    }
}