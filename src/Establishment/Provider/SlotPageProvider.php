<?php

namespace App\Establishment\Provider;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentSlot;
use App\Entity\User\User;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Enum\SlotStatus;
use App\Establishment\Exception\SlotUnavailableException;
use App\Establishment\Helper\GeoHelper;
use App\Establishment\Presenter\SlotPresenter;
use App\Repository\Establishment\EstablishmentSlotRepository;
use DateTimeImmutable;
use NeoPHP\Component\Session\SessionManagerInterface;

class SlotPageProvider
{
    public const OLD_BOOKING = 'app.booking.old';

    public const OTHER_SLOTS = 20;

    public function __construct(
        private EstablishmentSlotRepository $slots,
        private ShopPageProvider $shops,
        private SlotPresenter $presenter,
        private SessionManagerInterface $session,
    ) {
    }

    public function show(int $id, ?User $user, array $origin): array
    {
        $slot = $this->visibleSlot($id);
        $establishment = $slot->getEstablishment();
        $now = new DateTimeImmutable();

        return [
            'breadcrumb' => $this->shops->breadcrumb($establishment),
            'slot' => $this->presenter->detail($slot, $this->distance($establishment, $origin), $now),
            'others' => $this->presenter->cards($this->sameDaySlots($slot, $now), $now),
            'user' => $this->user($user),
            'form' => $this->pullOld($id) + [
                    'firstName' => $user?->getFirstName(),
                    'email' => $user?->getEmail(),
                    'phone' => $user?->getPhone(),
                    'note' => null,
                ],
        ];
    }

    public function remember(array $old): void
    {
        $this->session->set(self::OLD_BOOKING, $old);
    }

    private function visibleSlot(int $id): EstablishmentSlot
    {
        $slot = $this->slots->find($id);

        if (!$slot instanceof EstablishmentSlot) {
            throw new SlotUnavailableException();
        }

        $establishment = $slot->getEstablishment();

        if ($establishment === null || $establishment->getStatus() !== EstablishmentStatus::ONLINE) {
            throw new SlotUnavailableException();
        }

        if ($slot->getStatus() === SlotStatus::WITHDRAWN) {
            throw new SlotUnavailableException();
        }

        return $slot;
    }

    private function distance(CompanyEstablishment $establishment, array $origin): ?float
    {
        [$latitude, $longitude] = $origin;

        if ($latitude === null || $longitude === null) {
            return null;
        }

        return GeoHelper::between(
            (string) $latitude,
            (string) $longitude,
            $establishment->getLatitude(),
            $establishment->getLongitude(),
        );
    }

    private function sameDaySlots(EstablishmentSlot $slot, DateTimeImmutable $now): array
    {
        $day = $slot->getStartsAt()?->format('Y-m-d');
        $others = [];
        $available = $this->slots->findAvailableForEstablishment($slot->getEstablishment(), $now, self::OTHER_SLOTS);

        foreach ($available as $other) {
            if ($other !== $slot && $other->getStartsAt()?->format('Y-m-d') === $day) {
                $others[] = $other;
            }
        }

        return $others;
    }

    private function user(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return ['firstName' => $user->getFirstName(), 'email' => $user->getEmail()];
    }

    private function pullOld(int $id): array
    {
        $old = $this->session->get(self::OLD_BOOKING);
        $this->session->remove(self::OLD_BOOKING);

        if (!is_array($old) || ($old['slot'] ?? null) !== $id) {
            return [];
        }

        return $old['values'];
    }
}