<?php

namespace App\Booking\Event;

use App\Entity\Booking;

class BookingNoShowEvent
{
    public function __construct(
        private Booking $booking,
        private bool $customerSuspended = false,
    ) {
    }

    public function getBooking(): Booking
    {
        return $this->booking;
    }

    public function isCustomerSuspended(): bool
    {
        return $this->customerSuspended;
    }
}