<?php

namespace App\Booking\Event;

use App\Entity\Booking;
use App\Entity\User\User;

class BookingCancelledEvent
{
    public function __construct(
        private Booking $booking,
        private ?User $actor = null,
        private bool $slotReleased = false,
    ) {
    }

    public function getBooking(): Booking
    {
        return $this->booking;
    }

    public function getActor(): ?User
    {
        return $this->actor;
    }

    public function isSlotReleased(): bool
    {
        return $this->slotReleased;
    }
}