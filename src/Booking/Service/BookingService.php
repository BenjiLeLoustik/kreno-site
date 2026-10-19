<?php

namespace App\Booking\Service;

use App\Booking\Dto\BookingInput;
use App\Booking\Enum\BookingStatus;
use App\Booking\Event\BookingCancelledEvent;
use App\Booking\Event\BookingConfirmedEvent;
use App\Booking\Exception\AccountCannotBookException;
use App\Booking\Exception\AccountSuspendedException;
use App\Booking\Exception\BookingNotCancellableException;
use App\Booking\Exception\BookingNotOwnedException;
use App\Booking\Exception\FreeCancellationExpiredException;
use App\Booking\Exception\OwnSlotBookingException;
use App\Booking\Exception\PhoneRequiredException;
use App\Booking\Exception\SlotNoLongerOfferedException;
use App\Booking\Exception\SlotTooCloseException;
use App\Booking\Exception\SlotUnavailableException;
use App\Booking\Exception\UpcomingBookingLimitException;
use App\Booking\Exception\UpcomingOnlyCancellationException;
use App\Booking\Helper\ReferenceHelper;
use App\Entity\Booking;
use App\Entity\Establishment\EstablishmentSlot;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Enum\SlotStatus;
use App\Repository\BookingRepository;
use App\User\Enum\UserStatus;
use App\Website\Provider\WebsiteProvider;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class BookingService
{
    public const REASON_MAX_LENGTH = 255;

    public function __construct(
        private OrmManagerInterface $orm,
        private EventManagerInterface $events,
        private WebsiteProvider $website,
        private BookingRepository $bookings,
        private FeeService $fees,
    ) {
    }

    public function book(EstablishmentSlot $slot, User $customer, BookingInput $input): Booking
    {
        $booking = $this->orm->transactional(function () use ($slot, $customer, $input): Booking {
            $now = new DateTimeImmutable();
            $this->assertBookable($slot, $now);
            $this->assertCustomer($customer, $slot, $now);
            $this->completeProfile($customer, $input);

            $booking = $this->createBooking($slot, $customer, $input);
            $slot->addBooking($booking);
            $slot->setStatus(SlotStatus::BOOKED);

            $this->orm->persist($booking);
            $this->orm->flush();

            return $booking;
        });

        $this->events->dispatch(new BookingConfirmedEvent($booking));

        return $booking;
    }

    public function freeCancellationUntil(Booking $booking): ?DateTimeImmutable
    {
        $minutes = $this->website->getRule()->getFreeCancellationMinutes();

        return $booking->getSlot()?->getStartsAt()?->modify(sprintf('-%d minutes', $minutes));
    }

    public function canCustomerCancel(Booking $booking, ?DateTimeImmutable $now = null): bool
    {
        $until = $this->freeCancellationUntil($booking);

        if ($booking->getStatus() !== BookingStatus::CONFIRMED || $until === null) {
            return false;
        }

        return ($now ?? new DateTimeImmutable()) < $until;
    }

    public function cancelByCustomer(Booking $booking, User $customer, ?string $reason = null): Booking
    {
        if ($booking->getCustomer() !== $customer) {
            throw new BookingNotOwnedException();
        }

        if ($booking->getStatus() !== BookingStatus::CONFIRMED) {
            throw new BookingNotCancellableException();
        }

        if (!$this->canCustomerCancel($booking)) {
            throw new FreeCancellationExpiredException();
        }

        return $this->cancel(
            $booking,
            BookingStatus::CANCELLED_BY_CUSTOMER,
            $customer,
            $reason,
            true
        );
    }

    public function cancelByEstablishment(Booking $booking, User $actor, ?string $reason = null): Booking
    {
        if (!$this->isUpcoming($booking)) {
            throw new UpcomingOnlyCancellationException();
        }

        return $this->cancel(
            $booking,
            BookingStatus::CANCELLED_BY_ESTABLISHMENT,
            $actor,
            $reason,
            false
        );
    }

    public function cancelAllForCustomer(User $customer, string $reason): int
    {
        $bookings = $this->bookings->findUpcomingForCustomer($customer, new DateTimeImmutable());

        foreach ($bookings as $booking) {
            $this->cancel(
                $booking,
                BookingStatus::CANCELLED_BY_CUSTOMER,
                $customer,
                $reason,
                true
            );
        }

        return count($bookings);
    }

    private function createBooking(EstablishmentSlot $slot, User $customer, BookingInput $input): Booking
    {
        $booking = new Booking();
        $booking->setReference($this->newReference());
        $booking->setCustomer($customer);
        $booking->setStatus(BookingStatus::CONFIRMED);
        $booking->setNote($this->text($input->note));
        $booking->setPrice($slot->getPrice());
        $booking->setFee($this->fee($slot));

        return $booking;
    }

    private function fee(EstablishmentSlot $slot): int
    {
        $establishment = $slot->getEstablishment();

        if ($establishment === null) {
            return 0;
        }

        return $this->fees->feeFor($establishment);
    }

    private function newReference(): string
    {
        return ReferenceHelper::generate(fn (string $reference): bool => $this->bookings->referenceExists($reference));
    }

    private function isUpcoming(Booking $booking): bool
    {
        if ($booking->getStatus() !== BookingStatus::CONFIRMED) {
            return false;
        }

        $startsAt = $booking->getSlot()?->getStartsAt();

        return $startsAt === null || $startsAt > new DateTimeImmutable();
    }

    private function cancel(
        Booking $booking,
        BookingStatus $status,
        User $actor,
        ?string $reason,
        bool $releaseSlot,
    ): Booking {
        $now = new DateTimeImmutable();

        $booking->setStatus($status);
        $booking->setCancelledAt($now);
        $booking->setCancelledBy($actor);
        $booking->setCancellationReason($this->cancellationReason($reason));
        $booking->setFee(0);

        $released = $this->updateSlot($booking->getSlot(), $releaseSlot, $now);

        $this->orm->flush();
        $this->events->dispatch(new BookingCancelledEvent($booking, $actor, $released));

        return $booking;
    }

    private function cancellationReason(?string $reason): ?string
    {
        $reason = $this->text($reason);

        if ($reason === null) {
            return null;
        }

        return mb_substr($reason, 0, self::REASON_MAX_LENGTH);
    }

    private function updateSlot(?EstablishmentSlot $slot, bool $releaseSlot, DateTimeImmutable $now): bool
    {
        if ($slot === null) {
            return false;
        }

        if (!$releaseSlot) {
            $slot->setStatus(SlotStatus::WITHDRAWN);
            $slot->setWithdrawnAt($now);

            return false;
        }

        if ($this->canRelease($slot, $now)) {
            $slot->setStatus(SlotStatus::AVAILABLE);

            return true;
        }

        $slot->setStatus(SlotStatus::EXPIRED);

        return false;
    }

    private function canRelease(EstablishmentSlot $slot, DateTimeImmutable $now): bool
    {
        $visibleUntil = $slot->getVisibleUntil();

        if ($visibleUntil === null || $visibleUntil <= $now) {
            return false;
        }

        return $slot->getEstablishment()?->getStatus() === EstablishmentStatus::ONLINE;
    }

    private function assertBookable(EstablishmentSlot $slot, DateTimeImmutable $now): void
    {
        $leadMinutes = $this->website->getRule()->getBookingLeadMinutes();

        if ($slot->getStatus() !== SlotStatus::AVAILABLE) {
            throw new SlotUnavailableException();
        }

        if ($slot->getEstablishment()?->getStatus() !== EstablishmentStatus::ONLINE) {
            throw new SlotUnavailableException();
        }

        if ($slot->getVisibleUntil() === null || $slot->getVisibleUntil() <= $now) {
            throw new SlotNoLongerOfferedException();
        }

        $startsAt = $slot->getStartsAt();

        if ($startsAt === null || $startsAt->modify(sprintf('-%d minutes', $leadMinutes)) <= $now) {
            throw new SlotTooCloseException($leadMinutes);
        }
    }

    private function assertCustomer(User $customer, EstablishmentSlot $slot, DateTimeImmutable $now): void
    {
        $maxUpcoming = $this->website->getRule()->getMaxUpcomingBookingsPerUser();

        if ($customer->getStatus() === UserStatus::SUSPENDED) {
            throw new AccountSuspendedException($customer->getSuspendedUntil());
        }

        if ($customer->getStatus() !== UserStatus::ACTIVE) {
            throw new AccountCannotBookException();
        }

        if ($this->isMember($customer, $slot)) {
            throw new OwnSlotBookingException();
        }

        if ($this->bookings->countUpcomingForCustomer($customer, $now) >= $maxUpcoming) {
            throw new UpcomingBookingLimitException($maxUpcoming);
        }
    }

    private function isMember(User $customer, EstablishmentSlot $slot): bool
    {
        foreach ($slot->getEstablishment()?->getCompany()?->getMembers() ?? [] as $member) {
            if ($member->getUser() === $customer) {
                return true;
            }
        }

        return false;
    }

    private function completeProfile(User $customer, BookingInput $input): void
    {
        $phone = $this->text($input->phone);

        if (!$this->hasPhone($customer) && $phone !== null) {
            $customer->setPhone($phone);
        }

        if (!$this->hasPhone($customer)) {
            throw new PhoneRequiredException();
        }
    }

    private function hasPhone(User $customer): bool
    {
        return $customer->getPhone() !== null && $customer->getPhone() !== '';
    }

    private function text(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return $value;
    }
}