<?php

namespace App\Booking\Service;

use App\Booking\Enum\BookingStatus;
use App\Booking\Event\BookingHonoredEvent;
use App\Booking\Event\BookingNoShowEvent;
use App\Booking\Exception\BookingAlreadyClosedException;
use App\Booking\Exception\BookingNotStartedException;
use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\User\Service\SanctionService;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class AttendanceService
{
    public const CLOSING_HOURS = 48;

    public function __construct(
        private OrmManagerInterface $orm,
        private EventManagerInterface $events,
        private BookingRepository $bookings,
        private SanctionService $sanctions,
    ) {
    }

    public function canMark(Booking $booking, ?DateTimeImmutable $now = null): bool
    {
        $startsAt = $booking->getSlot()?->getStartsAt();

        if ($booking->getStatus() !== BookingStatus::CONFIRMED || $startsAt === null) {
            return false;
        }

        return $startsAt <= ($now ?? new DateTimeImmutable());
    }

    public function markHonored(Booking $booking): Booking
    {
        $this->assertMarkable($booking);

        $booking->setStatus(BookingStatus::HONORED);
        $booking->setHonoredAt(new DateTimeImmutable());
        $this->orm->flush();

        $this->events->dispatch(new BookingHonoredEvent($booking));

        return $booking;
    }

    public function markNoShow(Booking $booking): Booking
    {
        $this->assertMarkable($booking);

        $booking->setStatus(BookingStatus::NO_SHOW);
        $booking->setNoShowAt(new DateTimeImmutable());
        $this->orm->flush();

        $customer = $booking->getCustomer();
        $suspended = $customer !== null && $this->sanctions->applyNoShowPolicy($customer);

        $this->events->dispatch(new BookingNoShowEvent($booking, $suspended));

        return $booking;
    }

    public function closeFinished(?DateTimeImmutable $now = null): int
    {
        $now ??= new DateTimeImmutable();
        $limit = $now->modify(sprintf('-%d hours', self::CLOSING_HOURS));
        $bookings = $this->bookings->findConfirmedEndedBefore($limit);

        foreach ($bookings as $booking) {
            $booking->setStatus(BookingStatus::HONORED);
            $booking->setHonoredAt($now);
        }

        $this->orm->flush();

        foreach ($bookings as $booking) {
            $this->events->dispatch(new BookingHonoredEvent($booking, true));
        }

        return count($bookings);
    }

    private function assertMarkable(Booking $booking): void
    {
        if ($booking->getStatus() !== BookingStatus::CONFIRMED) {
            throw new BookingAlreadyClosedException();
        }

        if (!$this->canMark($booking)) {
            throw new BookingNotStartedException();
        }
    }
}