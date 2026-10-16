<?php

namespace App\Booking\Service;

use App\Booking\Dto\BookingInput;
use App\Booking\Enum\BookingStatus;
use App\Booking\Event\BookingCancelledEvent;
use App\Booking\Event\BookingConfirmedEvent;
use App\Booking\Helper\ReferenceHelper;
use App\Entity\Booking;
use App\Entity\Establishment\EstablishmentSlot;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Enum\SlotStatus;
use App\Repository\BookingRepository;
use App\Shared\Exception\BusinessRuleException;
use App\User\Enum\UserStatus;
use App\Website\Provider\WebsiteProvider;
use DateTimeImmutable;
use NeoPHP\Component\Event\EventManagerInterface;
use NeoPHP\Package\Orm\OrmManagerInterface;

class BookingService
{
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

            $note = trim((string) $input->note);
            $establishment = $slot->getEstablishment();

            $booking = new Booking();
            $booking->setReference(ReferenceHelper::generate(fn (string $reference): bool => $this->bookings->referenceExists($reference)));
            $booking->setCustomer($customer);
            $booking->setStatus(BookingStatus::CONFIRMED);
            $booking->setNote($note !== '' ? $note : null);
            $booking->setPrice($slot->getPrice());
            $booking->setFee($establishment !== null ? $this->fees->feeFor($establishment) : 0);

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
        return $booking->getSlot()?->getStartsAt()?->modify(sprintf('-%d minutes', $this->website->getRule()->getFreeCancellationMinutes()));
    }

    public function canCustomerCancel(Booking $booking, ?DateTimeImmutable $now = null): bool
    {
        $until = $this->freeCancellationUntil($booking);

        return $booking->getStatus() === BookingStatus::CONFIRMED && $until !== null && ($now ?? new DateTimeImmutable()) < $until;
    }

    public function cancelByCustomer(Booking $booking, User $customer, ?string $reason = null): Booking
    {
        if ($booking->getCustomer() !== $customer) {
            throw new BusinessRuleException('Cette réservation ne vous appartient pas.');
        }

        if ($booking->getStatus() !== BookingStatus::CONFIRMED) {
            throw new BusinessRuleException('Cette réservation ne peut plus être annulée.');
        }

        if (!$this->canCustomerCancel($booking)) {
            throw new BusinessRuleException('Le délai d\'annulation gratuite est dépassé : préviens directement l\'établissement.');
        }

        return $this->cancel($booking, BookingStatus::CANCELLED_BY_CUSTOMER, $customer, $reason, true);
    }

    public function cancelByEstablishment(Booking $booking, User $actor, ?string $reason = null): Booking
    {
        $startsAt = $booking->getSlot()?->getStartsAt();

        if ($booking->getStatus() !== BookingStatus::CONFIRMED || ($startsAt !== null && $startsAt <= new DateTimeImmutable())) {
            throw new BusinessRuleException('Seul un rendez-vous à venir peut être annulé.');
        }

        return $this->cancel($booking, BookingStatus::CANCELLED_BY_ESTABLISHMENT, $actor, $reason, false);
    }

    public function cancelAllForCustomer(User $customer, string $reason): int
    {
        $bookings = $this->bookings->findUpcomingForCustomer($customer, new DateTimeImmutable());

        foreach ($bookings as $booking) {
            $this->cancel($booking, BookingStatus::CANCELLED_BY_CUSTOMER, $customer, $reason, true);
        }

        return count($bookings);
    }

    private function cancel(Booking $booking, BookingStatus $status, User $actor, ?string $reason, bool $releaseSlot): Booking
    {
        $now = new DateTimeImmutable();
        $slot = $booking->getSlot();
        $reason = trim((string) $reason);
        $released = false;

        $booking->setStatus($status);
        $booking->setCancelledAt($now);
        $booking->setCancelledBy($actor);
        $booking->setCancellationReason($reason !== '' ? mb_substr($reason, 0, 255) : null);
        $booking->setFee(0);

        if ($slot !== null) {
            if ($releaseSlot && $slot->getVisibleUntil() !== null && $slot->getVisibleUntil() > $now && $slot->getEstablishment()?->getStatus() === EstablishmentStatus::ONLINE) {
                $slot->setStatus(SlotStatus::AVAILABLE);
                $released = true;
            } elseif ($releaseSlot) {
                $slot->setStatus(SlotStatus::EXPIRED);
            } else {
                $slot->setStatus(SlotStatus::WITHDRAWN);
                $slot->setWithdrawnAt($now);
            }
        }

        $this->orm->flush();
        $this->events->dispatch(new BookingCancelledEvent($booking, $actor, $released));

        return $booking;
    }

    private function assertBookable(EstablishmentSlot $slot, DateTimeImmutable $now): void
    {
        $rule = $this->website->getRule();

        if ($slot->getStatus() !== SlotStatus::AVAILABLE || $slot->getEstablishment()?->getStatus() !== EstablishmentStatus::ONLINE) {
            throw new BusinessRuleException('Trop tard, ce créneau vient d\'être réservé ou retiré.');
        }

        if ($slot->getVisibleUntil() === null || $slot->getVisibleUntil() <= $now) {
            throw new BusinessRuleException('Ce créneau n\'est plus proposé.');
        }

        if ($slot->getStartsAt() === null || $slot->getStartsAt()->modify(sprintf('-%d minutes', $rule->getBookingLeadMinutes())) <= $now) {
            throw new BusinessRuleException('Ce créneau commence dans moins de {minutes} min : il ne peut plus être réservé.', ['minutes' => $rule->getBookingLeadMinutes()]);
        }
    }

    private function assertCustomer(User $customer, EstablishmentSlot $slot, DateTimeImmutable $now): void
    {
        $rule = $this->website->getRule();

        if ($customer->getStatus() === UserStatus::SUSPENDED) {
            throw new BusinessRuleException('Ton compte est suspendu jusqu\'au {date} : tu ne peux pas réserver d\'ici là.', ['date' => $customer->getSuspendedUntil()?->format('d/m/Y') ?? '—']);
        }

        if ($customer->getStatus() !== UserStatus::ACTIVE) {
            throw new BusinessRuleException('Ton compte ne permet pas de réserver.');
        }

        foreach ($slot->getEstablishment()?->getCompany()?->getMembers() ?? [] as $member) {
            if ($member->getUser() === $customer) {
                throw new BusinessRuleException('Tu ne peux pas réserver un créneau de ton propre établissement.');
            }
        }

        if ($this->bookings->countUpcomingForCustomer($customer, $now) >= $rule->getMaxUpcomingBookingsPerUser()) {
            throw new BusinessRuleException('Tu as déjà {max} réservation(s) à venir : c\'est le maximum autorisé.', ['max' => $rule->getMaxUpcomingBookingsPerUser()]);
        }
    }

    private function completeProfile(User $customer, BookingInput $input): void
    {
        if (($customer->getPhone() === null || $customer->getPhone() === '') && $input->phone !== null && trim($input->phone) !== '') {
            $customer->setPhone(trim($input->phone));
        }

        if ($customer->getPhone() === null || $customer->getPhone() === '') {
            throw new BusinessRuleException('Indique un numéro de téléphone pour que l\'établissement puisse te joindre.');
        }
    }
}