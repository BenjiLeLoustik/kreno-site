<?php

namespace App\Establishment\Presenter;

use App\Entity\Booking;
use App\Entity\Establishment\EstablishmentImage;
use App\Entity\Establishment\EstablishmentSlot;
use App\Establishment\Enum\SlotStatus;
use App\Establishment\Helper\CalendarHelper;
use App\Establishment\Helper\GeoHelper;
use App\Establishment\Model\SlotMatch;
use App\Repository\BookingRepository;
use App\Shared\Helper\DateHelper;
use App\Shared\Helper\DurationHelper;
use App\Shared\Helper\MoneyHelper;
use App\User\Helper\UserNameHelper;
use App\Website\Provider\WebsiteProvider;
use DateTimeImmutable;

class SlotPresenter
{
    public const KINDS = [
        'online' => ['status' => 'En ligne', 'agenda' => 'en ligne, pas encore réservé', 'cta' => 'Modifier dans Mes créneaux'],
        'booked' => ['status' => 'Réservé', 'agenda' => 'réservé par %s', 'cta' => 'Voir la réservation'],
        'expired' => ['status' => 'Expiré', 'agenda' => 'expiré sans réservation', 'cta' => 'Republier'],
        'removed' => ['status' => 'Retiré', 'agenda' => 'retiré', 'cta' => 'Republier'],
    ];

    public const INFOS = [
        ['title' => 'Paiement sur place', 'text' => 'Aucune carte demandée en ligne.'],
        ['title' => 'Annulation gratuite', 'text' => 'Jusqu’à %s avant le rendez-vous.'],
        ['title' => 'Place bloquée', 'text' => 'À ton nom dès la confirmation.'],
    ];

    public function __construct(
        private WebsiteProvider $website,
        private ImagePresenter $imagePresenter,
    ) {
    }

    public function kind(EstablishmentSlot $slot, ?DateTimeImmutable $now = null): string
    {
        return match ($slot->getStatus()) {
            SlotStatus::BOOKED => 'booked',
            SlotStatus::EXPIRED => 'expired',
            SlotStatus::WITHDRAWN => 'removed',
            SlotStatus::AVAILABLE => $slot->getVisibleUntil() !== null && $slot->getVisibleUntil() <= ($now ?? new DateTimeImmutable()) ? 'expired' : 'online',
        };
    }

    public function booking(EstablishmentSlot $slot): ?Booking
    {
        foreach ($slot->getBookings() as $booking) {
            if (in_array($booking->getStatus(), BookingRepository::KEPT_STATUSES, true)) {
                return $booking;
            }
        }

        return null;
    }

    public function forPro(EstablishmentSlot $slot, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $kind = $this->kind($slot, $now);
        $booking = $this->booking($slot);
        $customer = $booking?->getCustomer();

        return $this->prices($slot) + [
                'id' => $slot->getId(),
                'startsAt' => DateHelper::iso($slot->getStartsAt()),
                'date' => DateHelper::day($slot->getStartsAt()),
                'time' => DateHelper::time($slot->getStartsAt()),
                'endTime' => DateHelper::time($slot->getEndsAt()),
                'day' => mb_strtolower(CalendarHelper::dayLabel($slot->getStartsAt(), $now)),
                'what' => $slot->getService()?->getName(),
                'serviceId' => $slot->getService()?->getId(),
                'staff' => $slot->getStaff()?->getMember()?->getUser() !== null ? UserNameHelper::shortName($slot->getStaff()->getMember()->getUser()) : null,
                'kind' => $kind,
                'status' => self::KINDS[$kind]['status'],
                'client' => $customer !== null ? UserNameHelper::shortName($customer) : '—',
                'bookingId' => $booking?->getId(),
                'visibleUntil' => DateHelper::time($slot->getVisibleUntil()),
                'expiresLabel' => $kind === 'online' ? sprintf('expire à %s', DateHelper::time($slot->getVisibleUntil())) : null,
                'canWithdraw' => $kind === 'online',
                'canRepublish' => in_array($kind, ['expired', 'removed'], true),
            ];
    }

    public function forDashboard(EstablishmentSlot $slot, ?DateTimeImmutable $now = null): array
    {
        $row = $this->forPro($slot, $now);
        $row['detail'] = match ($row['kind']) {
            'booked' => sprintf('Réservé par %s · %s', $row['client'], $row['price']),
            'online' => sprintf('%s · %s', $row['discountLabel'], $row['expiresLabel']),
            'removed' => 'Retiré',
            default => 'Non réservé',
        };

        return $row;
    }

    public function forAgenda(EstablishmentSlot $slot, int $startHour, ?DateTimeImmutable $now = null): array
    {
        $row = $this->forPro($slot, $now);
        $startsAt = $slot->getStartsAt() ?? new DateTimeImmutable();

        return $row + [
                'weekday' => (int) $startsAt->format('N'),
                'top' => CalendarHelper::minutesOfDay($startsAt) - $startHour * 60,
                'height' => $slot->getService()?->getDuration() ?? 30,
                'statusText' => sprintf(self::KINDS[$row['kind']]['agenda'], $row['client']),
                'cta' => self::KINDS[$row['kind']]['cta'],
                'action' => match ($row['kind']) {
                    'booked' => 'booking',
                    'online' => 'slots',
                    default => 'republish',
                },
            ];
    }

    public function card(EstablishmentSlot $slot, ?float $distance = null, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $establishment = $slot->getEstablishment();
        $remainingMinutes = $slot->getVisibleUntil() !== null ? intdiv(max(0, $slot->getVisibleUntil()->getTimestamp() - $now->getTimestamp()), 60) : 0;
        $urgency = match (true) {
            $remainingMinutes <= 10 => 'last',
            $remainingMinutes <= 30 => 'soon',
            default => 'fresh',
        };
        $cover = $establishment?->getImages()->first();

        return $this->prices($slot) + [
                'id' => $slot->getId(),
                'startsAt' => DateHelper::iso($slot->getStartsAt()),
                'day' => CalendarHelper::dayLabel($slot->getStartsAt(), $now),
                'today' => $slot->getStartsAt()?->format('Y-m-d') === $now->format('Y-m-d'),
                'time' => DateHelper::time($slot->getStartsAt()),
                'duration' => DurationHelper::format($slot->getService()?->getDuration() ?? 0),
                'what' => $slot->getService()?->getName(),
                'category' => $establishment?->getCategory()?->getName(),
                'categorySlug' => $establishment?->getCategory()?->getSlug(),
                'pro' => $establishment?->getName(),
                'establishment' => [
                    'id' => $establishment?->getId(),
                    'slug' => $establishment?->getSlug(),
                    'city' => $establishment?->getCity()?->getSlug(),
                    'locality' => $establishment?->getLocality(),
                    'cover' => $cover instanceof EstablishmentImage ? $this->imagePresenter->url($cover) : null,
                ],
                'distance' => GeoHelper::format($distance),
                'distanceKm' => $distance !== null ? round($distance, 2) : null,
                'urgency' => $urgency,
                'badge' => $urgency === 'fresh' ? sprintf('-%d %%', $slot->getDiscount()) : sprintf('Plus que %d min', $remainingMinutes),
                'visibility' => $this->visibility($slot, $now),
                'remaining' => $slot->getVisibleUntil() !== null ? DurationHelper::remaining($slot->getVisibleUntil(), $now) : null,
            ];
    }

    public function cards(array $matches, ?DateTimeImmutable $now = null): array
    {
        return array_values(array_map(fn (SlotMatch|EstablishmentSlot $match): array => $match instanceof SlotMatch ? $this->card($match->getSlot(), $match->getDistance(), $now) : $this->card($match, null, $now), $matches));
    }

    public function detail(EstablishmentSlot $slot, ?float $distance = null, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $rule = $this->website->getRule();
        $establishment = $slot->getEstablishment();
        $startsAt = $slot->getStartsAt() ?? $now;
        $freeCancellationUntil = $startsAt->modify(sprintf('-%d minutes', $rule->getFreeCancellationMinutes()));
        $bookable = $slot->getStatus() === SlotStatus::AVAILABLE
            && $slot->getVisibleUntil() > $now
            && $startsAt->modify(sprintf('-%d minutes', $rule->getBookingLeadMinutes())) > $now;

        return $this->card($slot, $distance, $now) + [
                'bookable' => $bookable,
                'address' => trim(sprintf('%s, %s %s', $establishment?->getAddressLine1(), $establishment?->getPostalCode(), $establishment?->getLocality()), ', '),
                'freeCancellationUntil' => DateHelper::time($freeCancellationUntil),
                'freeCancellation' => $freeCancellationUntil > $now,
                'infos' => array_map(static fn (array $info): array => ['title' => $info['title'], 'text' => sprintf($info['text'], DurationHelper::format($rule->getFreeCancellationMinutes()))], self::INFOS),
            ];
    }

    public function visibility(EstablishmentSlot $slot, DateTimeImmutable $now): int
    {
        if ($slot->getVisibleUntil() === null || $slot->getCreatedAt() === null) {
            return 0;
        }

        $total = max(1, $slot->getVisibleUntil()->getTimestamp() - $slot->getCreatedAt()->getTimestamp());
        $left = max(0, $slot->getVisibleUntil()->getTimestamp() - $now->getTimestamp());

        return (int) round(min(1, $left / $total) * 100);
    }

    private function prices(EstablishmentSlot $slot): array
    {
        return [
            'originalPrice' => $slot->getOriginalPrice(),
            'old' => MoneyHelper::format($slot->getOriginalPrice()),
            'priceCents' => $slot->getPrice(),
            'price' => MoneyHelper::format($slot->getPrice()),
            'discount' => $slot->getDiscount(),
            'discountLabel' => sprintf('-%d %%', $slot->getDiscount()),
        ];
    }
}