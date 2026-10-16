<?php

namespace App\Booking\Presenter;

use App\Booking\Enum\BookingStatus;
use App\Booking\Helper\BookingStatusHelper;
use App\Booking\Service\AttendanceService;
use App\Booking\Service\BookingService;
use App\Booking\Service\ReminderService;
use App\Entity\Booking;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\User\User;
use App\Enum\Calendar\Weekday;
use App\Establishment\Helper\CalendarHelper;
use App\Repository\BookingRepository;
use App\Shared\Helper\DateHelper;
use App\Shared\Helper\DurationHelper;
use App\Shared\Helper\MoneyHelper;
use App\User\Helper\UserNameHelper;
use DateTimeImmutable;

class BookingPresenter
{
    public const REPORTABLE_STATUSES = [
        BookingStatus::CONFIRMED,
        BookingStatus::HONORED,
        BookingStatus::CANCELLED_BY_ESTABLISHMENT,
    ];

    public function __construct(
        private BookingService $bookingService,
        private AttendanceService $attendance,
        private ReminderService $reminders,
        private BookingRepository $bookings,
    ) {
    }

    public function base(Booking $booking, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $slot = $booking->getSlot();
        $startsAt = $slot?->getStartsAt();
        $originalPrice = $slot?->getOriginalPrice();

        return [
            'id' => $booking->getId(),
            'reference' => $booking->getReference(),
            'status' => $booking->getStatus()->value,
            'kind' => BookingStatusHelper::kind($booking, $now),
            'price' => MoneyHelper::format($booking->getPrice()),
            'priceCents' => $booking->getPrice(),
            'old' => MoneyHelper::format($originalPrice ?? $booking->getPrice()),
            'discountLabel' => $this->discountLabel($booking),
            'saved' => MoneyHelper::format(max(0, ($originalPrice ?? 0) - $booking->getPrice())),
            'createdAt' => DateHelper::iso($booking->getCreatedAt()),
            'startsAt' => DateHelper::iso($startsAt),
            'day' => $this->day($startsAt, $now),
            'date' => $this->date($startsAt),
            'when' => $this->when($startsAt),
            'time' => DateHelper::time($startsAt),
            'duration' => DurationHelper::format($slot?->getService()?->getDuration() ?? 0),
            'what' => $slot?->getService()?->getName(),
            'establishment' => $this->establishment($slot?->getEstablishment()),
            'staff' => $slot?->getStaff()?->getMember()?->getUser()?->getFirstName(),
        ];
    }

    public function forCustomer(Booking $booking, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();

        return $this->base($booking, $now) + [
                'statusLabel' => BookingStatusHelper::customerLabel($booking, $now),
                'in' => $this->customerIn($booking, $now),
                'canCancel' => $this->bookingService->canCustomerCancel($booking, $now),
                'freeCancellationUntil' => DateHelper::time($this->bookingService->freeCancellationUntil($booking)),
                'cancellationReason' => $booking->getCancellationReason(),
                'note' => $booking->getNote(),
            ];
    }

    public function forCustomerDetail(Booking $booking, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();

        return $this->forCustomer($booking, $now) + [
                'timeline' => $this->customerTimeline($booking, $now),
                'canReport' => $this->canReport($booking, $now),
            ];
    }

    public function forCustomerList(array $bookings, ?DateTimeImmutable $now = null): array
    {
        return array_values(array_map(fn (Booking $booking): array => $this->forCustomer($booking, $now), $bookings));
    }

    public function forPro(Booking $booking, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();

        return $this->base($booking, $now) + [
                'statusLabel' => BookingStatusHelper::proLabel($booking, $now),
                'client' => $this->clientName($booking->getCustomer()),
            ];
    }

    public function forProList(array $bookings, ?DateTimeImmutable $now = null): array
    {
        return array_values(array_map(fn (Booking $booking): array => $this->forPro($booking, $now), $bookings));
    }

    public function forProDetail(Booking $booking, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $row = $this->forPro($booking, $now);
        $status = $booking->getStatus();
        $confirmed = $status === BookingStatus::CONFIRMED;

        return $row + [
                'customer' => $this->proCustomer($booking),
                'note' => $booking->getNote(),
                'canMark' => $this->attendance->canMark($booking, $now),
                'canCancel' => $confirmed && $booking->getSlot()?->getStartsAt() > $now,
                'closed' => !$confirmed,
                'closedText' => $status->proClosedText($row['client']),
                'cancellationReason' => $booking->getCancellationReason(),
            ];
    }

    public function forAdmin(Booking $booking, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();

        return $this->base($booking, $now) + [
                'statusLabel' => BookingStatusHelper::adminLabel($booking, $now),
                'client' => $this->clientName($booking->getCustomer()),
                'shop' => $booking->getSlot()?->getEstablishment()?->getName(),
            ];
    }

    public function forAdminList(array $bookings, ?DateTimeImmutable $now = null): array
    {
        return array_values(
            array_map(
                fn (Booking $booking): array => $this->forAdmin($booking, $now)
                , $bookings
            )
        );
    }

    public function forAdminDetail(Booking $booking, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();

        return $this->forAdmin($booking, $now) + [
                'customer' => $this->adminCustomer($booking->getCustomer()),
                'fee' => MoneyHelper::format($booking->getFee()),
                'note' => $booking->getNote(),
                'cancelledAt' => DateHelper::iso($booking->getCancelledAt()),
                'cancelledBy' => $this->fullName($booking->getCancelledBy()),
                'cancellationReason' => $booking->getCancellationReason(),
                'honoredAt' => DateHelper::iso($booking->getHonoredAt()),
                'noShowAt' => DateHelper::iso($booking->getNoShowAt()),
                'timeline' => $this->customerTimeline($booking, $now),
            ];
    }

    public function confirmation(Booking $booking, ?DateTimeImmutable $now = null): array
    {
        $data = $this->forCustomer($booking, $now);
        $establishment = $data['establishment']['name'] ?? 'L\'établissement';
        $message = '%s est prévenu. Un e-mail de confirmation vient de partir, avec le ticket et l\'adresse. Présente-toi 5 minutes avant.';

        return $data + [
                'title' => 'C\'est réservé.',
                'message' => sprintf($message, $establishment),
                'cancellationNotice' => $this->cancellationNotice($data['freeCancellationUntil']),
            ];
    }

    public function in(?DateTimeImmutable $startsAt, DateTimeImmutable $now): ?string
    {
        if ($startsAt === null || $startsAt <= $now) {
            return null;
        }

        $minutes = intdiv($startsAt->getTimestamp() - $now->getTimestamp(), 60);

        if ($minutes < 1440) {
            return 'dans ' . DurationHelper::format(max(1, $minutes));
        }

        $days = intdiv($minutes, 1440);

        if ($days > 1) {
            return sprintf('dans %d jours', $days);
        }

        return sprintf('dans %d jour', $days);
    }

    private function discountLabel(Booking $booking): ?string
    {
        $slot = $booking->getSlot();

        if ($slot === null) {
            return null;
        }

        return sprintf('-%d %%', $slot->getDiscount());
    }

    private function day(?DateTimeImmutable $startsAt, DateTimeImmutable $now): ?string
    {
        if ($startsAt === null) {
            return null;
        }

        return CalendarHelper::dayLabel($startsAt, $now);
    }

    private function date(?DateTimeImmutable $startsAt): ?string
    {
        if ($startsAt === null) {
            return null;
        }

        return CalendarHelper::shortDate($startsAt);
    }

    private function when(?DateTimeImmutable $startsAt): ?string
    {
        if ($startsAt === null) {
            return null;
        }

        $weekday = Weekday::of($startsAt)->short();

        return sprintf(
            '%s %d · %s',
            $weekday,
            (int) $startsAt->format('j'),
            $startsAt->format('H:i')
        );
    }

    private function establishment(?CompanyEstablishment $establishment): ?array
    {
        if ($establishment === null) {
            return null;
        }

        $address = sprintf(
            '%s, %s %s',
            $establishment->getAddressLine1(),
            $establishment->getPostalCode(),
            $establishment->getLocality(),
        );

        return [
            'id' => $establishment->getId(),
            'name' => $establishment->getName(),
            'slug' => $establishment->getSlug(),
            'city' => $establishment->getCity()?->getSlug(),
            'address' => trim($address, ', '),
            'phone' => $establishment->getPhone(),
            'directions' => $this->directions($establishment),
        ];
    }

    private function directions(CompanyEstablishment $establishment): ?string
    {
        $latitude = $establishment->getLatitude();
        $longitude = $establishment->getLongitude();

        if ($latitude === null || $longitude === null) {
            return null;
        }

        return sprintf('https://www.google.com/maps/dir/?api=1&destination=%s,%s', $latitude, $longitude);
    }

    private function customerIn(Booking $booking, DateTimeImmutable $now): ?string
    {
        if ($booking->getStatus() !== BookingStatus::CONFIRMED) {
            return null;
        }

        return $this->in($booking->getSlot()?->getStartsAt(), $now);
    }

    private function canReport(Booking $booking, DateTimeImmutable $now): bool
    {
        $startsAt = $booking->getSlot()?->getStartsAt();

        if ($startsAt === null || !in_array($booking->getStatus(), self::REPORTABLE_STATUSES, true)) {
            return false;
        }

        return $startsAt->modify(sprintf('+%d hours', AttendanceService::CLOSING_HOURS)) > $now;
    }

    private function clientName(?User $customer): string
    {
        if ($customer === null) {
            return '—';
        }

        return UserNameHelper::shortName($customer);
    }

    private function fullName(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        return UserNameHelper::fullName($user);
    }

    private function proCustomer(Booking $booking): ?array
    {
        $customer = $booking->getCustomer();

        if ($customer === null) {
            return null;
        }

        return [
            'name' => UserNameHelper::shortName($customer),
            'phone' => $customer->getPhone(),
            'history' => $this->customerHistory($customer, $booking->getSlot()?->getEstablishment()),
        ];
    }

    private function customerHistory(User $customer, ?CompanyEstablishment $establishment): string
    {
        $count = 0;
        $noShows = 0;

        if ($establishment !== null) {
            $count = $this->bookings->countForCustomerAtEstablishment($customer, $establishment);
            $noShows = $this->bookings->countForCustomerAtEstablishment(
                $customer,
                $establishment,
                BookingStatus::NO_SHOW,
            );
        }

        return sprintf('%s · %s', $this->bookingRank($count), $this->noShows($noShows));
    }

    private function bookingRank(int $count): string
    {
        if ($count <= 1) {
            return '1re réservation';
        }

        return sprintf('%de réservation', $count);
    }

    private function noShows(int $count): string
    {
        if ($count === 0) {
            return 'aucune absence';
        }

        if ($count > 1) {
            return sprintf('%d absences', $count);
        }

        return sprintf('%d absence', $count);
    }

    private function adminCustomer(?User $customer): ?array
    {
        if ($customer === null) {
            return null;
        }

        return [
            'id' => $customer->getId(),
            'name' => UserNameHelper::fullName($customer),
            'email' => $customer->getEmail(),
            'phone' => $customer->getPhone(),
            'status' => $customer->getStatus()->value,
        ];
    }

    private function cancellationNotice(?string $freeCancellationUntil): ?string
    {
        if ($freeCancellationUntil === null) {
            return null;
        }

        return sprintf('Empêché ? Annulation gratuite jusqu\'à %s depuis ton compte.', $freeCancellationUntil);
    }

    private function customerTimeline(Booking $booking, DateTimeImmutable $now): array
    {
        $slot = $booking->getSlot();
        $name = $slot?->getEstablishment()?->getName() ?? 'L\'établissement';
        $createdAt = $booking->getCreatedAt();
        $timeline = [
            $this->step($createdAt, sprintf('Réservation confirmée, %s prévenu', $name), true),
            $this->step($createdAt, 'E-mail de confirmation envoyé', true),
        ];

        if ($booking->getCancelledAt() !== null) {
            $timeline[] = $this->step(
                $booking->getCancelledAt(),
                $this->cancellationLabel($booking),
                true,
                'danger'
            );

            return $timeline;
        }

        $startsAt = $slot?->getStartsAt();

        if ($startsAt !== null) {
            $reminder = $this->reminders->remindedAt($startsAt);

            if ($reminder > $createdAt) {
                $timeline[] = $this->reminderStep($reminder, $now);
            }

            $timeline[] = $this->step($startsAt, 'Rendez-vous', $startsAt <= $now);
        }

        if ($booking->getHonoredAt() !== null) {
            $timeline[] = $this->step($booking->getHonoredAt(), 'Rendez-vous honoré', true);
        }

        if ($booking->getNoShowAt() !== null) {
            $label = 'Absence signalée par l\'établissement';
            $timeline[] = $this->step($booking->getNoShowAt(), $label, true, 'danger');
        }

        return $timeline;
    }

    private function cancellationLabel(Booking $booking): string
    {
        if ($booking->getStatus() === BookingStatus::CANCELLED_BY_ESTABLISHMENT) {
            return 'Réservation annulée par l\'établissement';
        }

        return 'Réservation annulée par vous, sans frais';
    }

    private function reminderStep(DateTimeImmutable $reminder, DateTimeImmutable $now): array
    {
        if ($reminder <= $now) {
            return $this->step($reminder, 'Rappel par e-mail', true);
        }

        return $this->step($reminder, 'Rappel par e-mail (à venir)', false);
    }

    private function step(?DateTimeImmutable $at, string $label, bool $done, string $tone = 'success'): array
    {
        if (!$done) {
            $tone = 'pending';
        }

        return [
            'at' => DateHelper::iso($at),
            'time' => DateHelper::time($at),
            'label' => $label,
            'done' => $done,
            'tone' => $tone,
        ];
    }
}