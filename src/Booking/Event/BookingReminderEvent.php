<?php

namespace App\Booking\Event;

use App\Entity\Booking;

class BookingReminderEvent
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