<?php

namespace App\Booking\Service;

use App\Booking\Event\BookingReminderEvent;
use App\Repository\BookingRepository;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;

class ReminderService
{
    public const REMINDER_MINUTES = 60;

    public const WINDOW_MINUTES = 5;

    public function __construct(
        private EventManagerInterface $events,
        private BookingRepository $bookings,
    ) {
    }

    public function remindedAt(DateTimeImmutable $startsAt): DateTimeImmutable
    {
        return $startsAt->modify(sprintf('-%d minutes', self::REMINDER_MINUTES));
    }

    public function sendDue(?DateTimeImmutable $now = null): int
    {
        $now ??= new DateTimeImmutable();
        $from = $now->modify(sprintf('+%d minutes', self::REMINDER_MINUTES - self::WINDOW_MINUTES));
        $to = $now->modify(sprintf('+%d minutes', self::REMINDER_MINUTES));
        $bookings = $this->bookings->findStartingBetween($from, $to);

        foreach ($bookings as $booking) {
            $this->events->dispatch(new BookingReminderEvent($booking));
        }

        return count($bookings);
    }
}