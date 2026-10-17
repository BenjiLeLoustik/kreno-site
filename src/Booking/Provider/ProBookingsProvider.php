<?php

namespace App\Booking\Provider;

use App\Booking\Enum\ProBookingTab;
use App\Booking\Exception\BookingNotFoundException;
use App\Booking\Presenter\BookingPresenter;
use App\Entity\Booking;
use App\Entity\Company\CompanyEstablishment;
use App\Repository\BookingRepository;
use DateTimeImmutable;

class ProBookingsProvider
{
    public function __construct(
        private BookingRepository $bookings,
        private BookingPresenter $presenter,
    ) {
    }

    public function index(CompanyEstablishment $establishment, string $tab): array
    {
        $selected = ProBookingTab::of($tab);
        $now = new DateTimeImmutable();
        $bookings = $this->bookings->findForEstablishment(
            $establishment,
            $selected->statuses(),
            $selected->after($now),
            $selected->before($now),
        );

        return [
            'tab' => $selected->value,
            'tabs' => $this->tabs($establishment, $now),
            'bookings' => $this->presenter->forProList($bookings, $now),
        ];
    }

    public function show(CompanyEstablishment $establishment, int $id): array
    {
        return [
            'booking' => $this->presenter->forProDetail($this->owned($establishment, $id)),
        ];
    }

    public function owned(CompanyEstablishment $establishment, int $id): Booking
    {
        $booking = $this->bookings->findOneInEstablishment($establishment, $id);

        if ($booking === null) {
            throw new BookingNotFoundException();
        }

        return $booking;
    }

    private function tabs(CompanyEstablishment $establishment, DateTimeImmutable $now): array
    {
        $tabs = [];

        foreach (ProBookingTab::cases() as $tab) {
            $tabs[] = [
                'key' => $tab->value,
                'label' => $tab->label(),
                'count' => $this->bookings->countForEstablishment(
                    $establishment,
                    $tab->statuses(),
                    $tab->after($now),
                    $tab->before($now),
                ),
            ];
        }

        return $tabs;
    }
}