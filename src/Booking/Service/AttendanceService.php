<?php

namespace App\Booking\Service;

use App\Booking\Enum\BookingStatus;
use App\Booking\Event\BookingHonoredEvent;
use App\Booking\Event\BookingNoShowEvent;
use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Shared\Exception\BusinessRuleException;
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

        return $booking->getStatus() === BookingStatus::CONFIRMED && $startsAt !== null && $startsAt <= ($now ?? new DateTimeImmutable());
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

        $suspended = $booking->getCustomer() !== null && $this->sanctions->applyNoShowPolicy($booking->getCustomer());

        $this->events->dispatch(new BookingNoShowEvent($booking, $suspended));

        return $booking;
    }

    public function closeFinished(?DateTimeImmutable $now = null): int
    {
        $now ??= new DateTimeImmutable();
        $bookings = $this->bookings->findConfirmedEndedBefore($now->modify(sprintf('-%d hours', self::CLOSING_HOURS)));

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
            throw new BusinessRuleException('Ce rendez-vous est déjà clôturé.');
        }

        if (!$this->canMark($booking)) {
            throw new BusinessRuleException('Le rendez-vous n\'a pas encore commencé.');
        }
    }
}