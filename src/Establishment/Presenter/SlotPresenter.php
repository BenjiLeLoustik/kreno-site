<?php

namespace App\Establishment\Presenter;

use App\Entity\Booking;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentImage;
use App\Entity\Establishment\EstablishmentSlot;
use App\Entity\User\User;
use App\Establishment\Enum\SlotInfo;
use App\Establishment\Enum\SlotKind;
use App\Establishment\Enum\SlotStatus;
use App\Establishment\Enum\SlotUrgency;
use App\Establishment\Helper\CalendarHelper;
use App\Establishment\Helper\GeoHelper;
use App\Establishment\Model\SlotMatch;
use App\Repository\BookingRepository;
use App\Shared\Helper\DateHelper;
use App\Shared\Helper\DurationHelper;
use App\Shared\Helper\MoneyHelper;
use App\Shared\Helper\Translation;
use App\User\Helper\UserNameHelper;
use App\Website\Provider\WebsiteProvider;
use DateTimeImmutable;

class SlotPresenter
{
    public const DEFAULT_DURATION = 30;

    public function __construct(
        private WebsiteProvider $website,
        private ImagePresenter $imagePresenter,
    ) {
    }

    public function status(EstablishmentSlot $slot, ?DateTimeImmutable $now = null): SlotKind
    {
        return match ($slot->getStatus()) {
            SlotStatus::BOOKED => SlotKind::BOOKED,
            SlotStatus::EXPIRED => SlotKind::EXPIRED,
            SlotStatus::WITHDRAWN => SlotKind::REMOVED,
            SlotStatus::AVAILABLE => $this->availableKind($slot, $now ?? new DateTimeImmutable()),
        };
    }

    public function kind(EstablishmentSlot $slot, ?DateTimeImmutable $now = null): string
    {
        return $this->status($slot, $now)->value;
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
        $kind = $this->status($slot, $now);
        $booking = $this->booking($slot);

        return $this->prices($slot) + [
                'id' => $slot->getId(),
                'startsAt' => DateHelper::iso($slot->getStartsAt()),
                'date' => DateHelper::day($slot->getStartsAt()),
                'time' => DateHelper::time($slot->getStartsAt()),
                'endTime' => DateHelper::time($slot->getEndsAt()),
                'day' => mb_strtolower(CalendarHelper::dayLabel($slot->getStartsAt(), $now)),
                'what' => $slot->getService()?->getName(),
                'serviceId' => $slot->getService()?->getId(),
                'staff' => $this->shortName($slot->getStaff()?->getMember()?->getUser()),
                'kind' => $kind->value,
                'status' => $kind->label(),
                'client' => $this->shortName($booking?->getCustomer()) ?? '—',
                'bookingId' => $booking?->getId(),
                'visibleUntil' => DateHelper::time($slot->getVisibleUntil()),
                'expiresLabel' => $this->expiresLabel($slot, $kind),
                'canWithdraw' => $kind === SlotKind::ONLINE,
                'canRepublish' => $kind->isRepublishable(),
            ];
    }

    public function forDashboard(EstablishmentSlot $slot, ?DateTimeImmutable $now = null): array
    {
        $row = $this->forPro($slot, $now);
        $row['detail'] = $this->dashboardDetail($row);

        return $row;
    }

    public function forAgenda(EstablishmentSlot $slot, int $startHour, ?DateTimeImmutable $now = null): array
    {
        $row = $this->forPro($slot, $now);
        $kind = SlotKind::from($row['kind']);
        $startsAt = $slot->getStartsAt() ?? new DateTimeImmutable();

        return $row + [
                'weekday' => (int) $startsAt->format('N'),
                'top' => CalendarHelper::minutesOfDay($startsAt) - $startHour * 60,
                'height' => $slot->getService()?->getDuration() ?? self::DEFAULT_DURATION,
                'statusText' => $kind->agendaText($row['client']),
                'cta' => $kind->cta(),
                'action' => $kind->action(),
            ];
    }

    public function card(EstablishmentSlot $slot, ?float $distance = null, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $establishment = $slot->getEstablishment();
        $remainingMinutes = $this->remainingMinutes($slot, $now);
        $urgency = SlotUrgency::fromRemainingMinutes($remainingMinutes);

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
                'establishment' => $this->cardEstablishment($establishment),
                'distance' => GeoHelper::format($distance),
                'distanceKm' => $this->distanceKm($distance),
                'urgency' => $urgency->value,
                'badge' => $this->badge($slot, $urgency, $remainingMinutes),
                'visibility' => $this->visibility($slot, $now),
                'remaining' => $this->remaining($slot, $now),
            ];
    }

    public function cards(array $matches, ?DateTimeImmutable $now = null): array
    {
        $cards = [];

        foreach ($matches as $match) {
            if ($match instanceof SlotMatch) {
                $cards[] = $this->card($match->getSlot(), $match->getDistance(), $now);
                continue;
            }

            $cards[] = $this->card($match, null, $now);
        }

        return $cards;
    }

    public function detail(EstablishmentSlot $slot, ?float $distance = null, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $rule = $this->website->getRule();
        $startsAt = $slot->getStartsAt() ?? $now;
        $freeCancellationUntil = $startsAt->modify(sprintf('-%d minutes', $rule->getFreeCancellationMinutes()));

        return $this->card($slot, $distance, $now) + [
                'bookable' => $this->isBookable($slot, $startsAt, $now),
                'address' => $this->address($slot->getEstablishment()),
                'freeCancellationUntil' => DateHelper::time($freeCancellationUntil),
                'freeCancellation' => $freeCancellationUntil > $now,
                'infos' => $this->infos(DurationHelper::format($rule->getFreeCancellationMinutes())),
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

    private function availableKind(EstablishmentSlot $slot, DateTimeImmutable $now): SlotKind
    {
        $visibleUntil = $slot->getVisibleUntil();

        if ($visibleUntil !== null && $visibleUntil <= $now) {
            return SlotKind::EXPIRED;
        }

        return SlotKind::ONLINE;
    }

    private function shortName(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        return UserNameHelper::shortName($user);
    }

    private function expiresLabel(EstablishmentSlot $slot, SlotKind $kind): ?string
    {
        if ($kind !== SlotKind::ONLINE) {
            return null;
        }

        return sprintf(Translation::translate('expire à %s'), DateHelper::time($slot->getVisibleUntil()));
    }

    private function dashboardDetail(array $row): string
    {
        return match (SlotKind::from($row['kind'])) {
            SlotKind::BOOKED => sprintf('Réservé par %s · %s', $row['client'], $row['price']),
            SlotKind::ONLINE => sprintf('%s · %s', $row['discountLabel'], $row['expiresLabel']),
            SlotKind::REMOVED => 'Retiré',
            SlotKind::EXPIRED => 'Non réservé',
        };
    }

    private function remainingMinutes(EstablishmentSlot $slot, DateTimeImmutable $now): int
    {
        $visibleUntil = $slot->getVisibleUntil();

        if ($visibleUntil === null) {
            return 0;
        }

        return intdiv(max(0, $visibleUntil->getTimestamp() - $now->getTimestamp()), 60);
    }

    private function remaining(EstablishmentSlot $slot, DateTimeImmutable $now): ?string
    {
        $visibleUntil = $slot->getVisibleUntil();

        if ($visibleUntil === null) {
            return null;
        }

        return DurationHelper::remaining($visibleUntil, $now);
    }

    private function badge(EstablishmentSlot $slot, SlotUrgency $urgency, int $remainingMinutes): string
    {
        if ($urgency === SlotUrgency::FRESH) {
            return sprintf('-%d %%', $slot->getDiscount());
        }

        return sprintf(Translation::translate('Plus que %d min'), $remainingMinutes);
    }

    private function distanceKm(?float $distance): ?float
    {
        if ($distance === null) {
            return null;
        }

        return round($distance, 2);
    }

    private function cardEstablishment(?CompanyEstablishment $establishment): array
    {
        $cover = $establishment?->getImages()->first();

        return [
            'id' => $establishment?->getId(),
            'slug' => $establishment?->getSlug(),
            'city' => $establishment?->getCity()?->getSlug(),
            'locality' => $establishment?->getLocality(),
            'cover' => $cover instanceof EstablishmentImage ? $this->imagePresenter->url($cover) : null,
        ];
    }

    private function isBookable(EstablishmentSlot $slot, DateTimeImmutable $startsAt, DateTimeImmutable $now): bool
    {
        if ($slot->getStatus() !== SlotStatus::AVAILABLE || !($slot->getVisibleUntil() > $now)) {
            return false;
        }

        $leadMinutes = $this->website->getRule()->getBookingLeadMinutes();

        return $startsAt->modify(sprintf('-%d minutes', $leadMinutes)) > $now;
    }

    private function address(?CompanyEstablishment $establishment): string
    {
        $address = sprintf(
            '%s, %s %s',
            $establishment?->getAddressLine1(),
            $establishment?->getPostalCode(),
            $establishment?->getLocality(),
        );

        return trim($address, ', ');
    }

    private function infos(string $cancellationDelay): array
    {
        $infos = [];

        foreach (SlotInfo::cases() as $info) {
            $infos[] = [
                'title' => $info->title(),
                'text' => $info->text($cancellationDelay)
            ];
        }

        return $infos;
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