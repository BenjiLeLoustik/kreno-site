<?php

namespace App\Booking\Event;

use App\Entity\Booking;

class BookingHonoredEvent
{
    public function __construct(
        private Booking $booking,
        private bool $automatic = false,
    ) {
    }

    public function getBooking(): Booking
    {
        return $this->booking;
    }

    public function isAutomatic(): bool
    {
        return $this->automatic;
    }
}