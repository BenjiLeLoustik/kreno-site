<?php

namespace App\Booking\Presenter;

use App\Booking\Enum\BookingStatus;
use App\Booking\Helper\BookingStatusHelper;
use App\Booking\Service\AttendanceService;
use App\Booking\Service\BookingService;
use App\Booking\Service\ReminderService;
use App\Entity\Booking;
use App\Establishment\Helper\CalendarHelper;
use App\Repository\BookingRepository;
use App\Shared\Helper\DateHelper;
use App\Shared\Helper\DurationHelper;
use App\Shared\Helper\MoneyHelper;
use App\User\Helper\UserNameHelper;
use DateTimeImmutable;

class BookingPresenter
{
    public const PRO_CLOSED_TEXTS = [
        'done' => 'Rendez-vous marqué comme honoré.',
        'noshow' => 'Absence enregistrée et signalée à Kréno.',
        'cancelled' => 'Le client a annulé ce rendez-vous.',
        'cancelled_by_establishment' => 'Rendez-vous annulé. %s est prévenu(e) par e-mail et d’autres créneaux lui sont proposés.',
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
        $establishment = $slot?->getEstablishment();
        $startsAt = $slot?->getStartsAt();

        return [
            'id' => $booking->getId(),
            'reference' => $booking->getReference(),
            'status' => $booking->getStatus()->value,
            'kind' => BookingStatusHelper::kind($booking, $now),
            'price' => MoneyHelper::format($booking->getPrice()),
            'priceCents' => $booking->getPrice(),
            'old' => MoneyHelper::format($slot?->getOriginalPrice() ?? $booking->getPrice()),
            'discountLabel' => $slot !== null ? sprintf('-%d %%', $slot->getDiscount()) : null,
            'saved' => MoneyHelper::format(max(0, ($slot?->getOriginalPrice() ?? 0) - $booking->getPrice())),
            'createdAt' => DateHelper::iso($booking->getCreatedAt()),
            'startsAt' => DateHelper::iso($startsAt),
            'day' => $startsAt !== null ? CalendarHelper::dayLabel($startsAt, $now) : null,
            'date' => $startsAt !== null ? CalendarHelper::shortDate($startsAt) : null,
            'when' => $startsAt !== null ? sprintf('%s %d · %s', CalendarHelper::SHORT_WEEKDAYS[(int) $startsAt->format('N')], (int) $startsAt->format('j'), $startsAt->format('H:i')) : null,
            'time' => DateHelper::time($startsAt),
            'duration' => DurationHelper::format($slot?->getService()?->getDuration() ?? 0),
            'what' => $slot?->getService()?->getName(),
            'establishment' => $establishment === null ? null : [
                'id' => $establishment->getId(),
                'name' => $establishment->getName(),
                'slug' => $establishment->getSlug(),
                'city' => $establishment->getCity()?->getSlug(),
                'address' => trim(sprintf('%s, %s %s', $establishment->getAddressLine1(), $establishment->getPostalCode(), $establishment->getLocality()), ', '),
                'phone' => $establishment->getPhone(),
                'directions' => $establishment->getLatitude() !== null && $establishment->getLongitude() !== null
                    ? sprintf('https://www.google.com/maps/dir/?api=1&destination=%s,%s', $establishment->getLatitude(), $establishment->getLongitude())
                    : null,
            ],
            'staff' => $slot?->getStaff()?->getMember()?->getUser()?->getFirstName(),
        ];
    }

    public function forCustomer(Booking $booking, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $until = $this->bookingService->freeCancellationUntil($booking);

        return $this->base($booking, $now) + [
                'statusLabel' => BookingStatusHelper::customerLabel($booking, $now),
                'in' => $booking->getStatus() === BookingStatus::CONFIRMED ? $this->in($booking->getSlot()?->getStartsAt(), $now) : null,
                'canCancel' => $this->bookingService->canCustomerCancel($booking, $now),
                'freeCancellationUntil' => DateHelper::time($until),
                'cancellationReason' => $booking->getCancellationReason(),
                'note' => $booking->getNote(),
            ];
    }

    public function forCustomerDetail(Booking $booking, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();

        return $this->forCustomer($booking, $now) + [
                'timeline' => $this->customerTimeline($booking, $now),
                'canReport' => in_array($booking->getStatus(), [BookingStatus::CONFIRMED, BookingStatus::HONORED, BookingStatus::CANCELLED_BY_ESTABLISHMENT], true)
                    && $booking->getSlot()?->getStartsAt() !== null
                    && $booking->getSlot()->getStartsAt()->modify(sprintf('+%d hours', AttendanceService::CLOSING_HOURS)) > $now,
            ];
    }

    public function forCustomerList(array $bookings, ?DateTimeImmutable $now = null): array
    {
        return array_values(array_map(fn (Booking $booking): array => $this->forCustomer($booking, $now), $bookings));
    }

    public function forPro(Booking $booking, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $customer = $booking->getCustomer();

        return $this->base($booking, $now) + [
                'statusLabel' => BookingStatusHelper::proLabel($booking, $now),
                'client' => $customer !== null ? UserNameHelper::shortName($customer) : '—',
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
        $customer = $booking->getCustomer();
        $establishment = $booking->getSlot()?->getEstablishment();
        $count = $customer !== null && $establishment !== null ? $this->bookings->countForCustomerAtEstablishment($customer, $establishment) : 0;
        $noShows = $customer !== null && $establishment !== null ? $this->bookings->countForCustomerAtEstablishment($customer, $establishment, BookingStatus::NO_SHOW) : 0;
        $closedText = self::PRO_CLOSED_TEXTS[$row['kind']] ?? null;

        return $row + [
                'customer' => $customer === null ? null : [
                    'name' => UserNameHelper::shortName($customer),
                    'phone' => $customer->getPhone(),
                    'history' => sprintf('%s · %s', $count <= 1 ? '1re réservation' : $count . 'e réservation', $noShows === 0 ? 'aucune absence' : sprintf('%d absence%s', $noShows, $noShows > 1 ? 's' : '')),
                ],
                'note' => $booking->getNote(),
                'canMark' => $this->attendance->canMark($booking, $now),
                'canCancel' => $booking->getStatus() === BookingStatus::CONFIRMED && $booking->getSlot()?->getStartsAt() > $now,
                'closed' => $booking->getStatus() !== BookingStatus::CONFIRMED,
                'closedText' => $closedText !== null ? sprintf($closedText, $row['client']) : null,
                'cancellationReason' => $booking->getCancellationReason(),
            ];
    }

    public function forAdmin(Booking $booking, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $customer = $booking->getCustomer();

        return $this->base($booking, $now) + [
                'statusLabel' => BookingStatusHelper::adminLabel($booking, $now),
                'client' => $customer !== null ? UserNameHelper::shortName($customer) : '—',
                'shop' => $booking->getSlot()?->getEstablishment()?->getName(),
            ];
    }

    public function forAdminList(array $bookings, ?DateTimeImmutable $now = null): array
    {
        return array_values(array_map(fn (Booking $booking): array => $this->forAdmin($booking, $now), $bookings));
    }

    public function forAdminDetail(Booking $booking, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $customer = $booking->getCustomer();

        return $this->forAdmin($booking, $now) + [
                'customer' => $customer === null ? null : [
                    'id' => $customer->getId(),
                    'name' => UserNameHelper::fullName($customer),
                    'email' => $customer->getEmail(),
                    'phone' => $customer->getPhone(),
                    'status' => $customer->getStatus()->value,
                ],
                'fee' => MoneyHelper::format($booking->getFee()),
                'note' => $booking->getNote(),
                'cancelledAt' => DateHelper::iso($booking->getCancelledAt()),
                'cancelledBy' => $booking->getCancelledBy() !== null ? UserNameHelper::fullName($booking->getCancelledBy()) : null,
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

        return $data + [
                'title' => 'C\'est réservé.',
                'message' => sprintf('%s est prévenu. Un e-mail de confirmation vient de partir, avec le ticket et l\'adresse. Présente-toi 5 minutes avant.', $establishment),
                'cancellationNotice' => $data['freeCancellationUntil'] !== null ? sprintf('Empêché ? Annulation gratuite jusqu\'à %s depuis ton compte.', $data['freeCancellationUntil']) : null,
            ];
    }

    public function in(?DateTimeImmutable $startsAt, DateTimeImmutable $now): ?string
    {
        if ($startsAt === null || $startsAt <= $now) {
            return null;
        }

        $minutes = intdiv($startsAt->getTimestamp() - $now->getTimestamp(), 60);

        if ($minutes >= 1440) {
            $days = intdiv($minutes, 1440);

            return sprintf('dans %d jour%s', $days, $days > 1 ? 's' : '');
        }

        return 'dans ' . DurationHelper::format(max(1, $minutes));
    }

    private function customerTimeline(Booking $booking, DateTimeImmutable $now): array
    {
        $slot = $booking->getSlot();
        $name = $slot?->getEstablishment()?->getName() ?? 'L\'établissement';
        $timeline = [];
        $add = static function (?DateTimeImmutable $at, string $label, bool $done, string $tone = 'success') use (&$timeline): void {
            $timeline[] = ['at' => DateHelper::iso($at), 'time' => DateHelper::time($at), 'label' => $label, 'done' => $done, 'tone' => $done ? $tone : 'pending'];
        };

        $add($booking->getCreatedAt(), sprintf('Réservation confirmée, %s prévenu', $name), true);
        $add($booking->getCreatedAt(), 'E-mail de confirmation envoyé', true);

        if ($booking->getCancelledAt() !== null) {
            $label = $booking->getStatus() === BookingStatus::CANCELLED_BY_ESTABLISHMENT ? 'Réservation annulée par l\'établissement' : 'Réservation annulée par vous, sans frais';
            $add($booking->getCancelledAt(), $label, true, 'danger');

            return $timeline;
        }

        if ($slot?->getStartsAt() !== null) {
            $reminder = $this->reminders->remindedAt($slot->getStartsAt());

            if ($reminder > $booking->getCreatedAt()) {
                $add($reminder, $reminder <= $now ? 'Rappel par e-mail' : 'Rappel par e-mail (à venir)', $reminder <= $now);
            }

            $add($slot->getStartsAt(), 'Rendez-vous', $slot->getStartsAt() <= $now);
        }

        if ($booking->getHonoredAt() !== null) {
            $add($booking->getHonoredAt(), 'Rendez-vous honoré', true);
        }

        if ($booking->getNoShowAt() !== null) {
            $add($booking->getNoShowAt(), 'Absence signalée par l\'établissement', true, 'danger');
        }

        return $timeline;
    }
}