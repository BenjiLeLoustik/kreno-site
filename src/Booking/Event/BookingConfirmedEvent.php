<?php

namespace App\Booking\Event;

use App\Entity\Booking;

class BookingConfirmedEvent
{
    public function __construct(
        private Booking $booking,
    ) {
    }

    public function getBooking(): Booking
    {
        return $this->booking;
    }
}