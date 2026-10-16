<?php

namespace App\Establishment\Service;

use App\Entity\Category;
use App\Entity\Establishment\EstablishmentSlot;
use App\Entity\User\User;
use App\Entity\User\UserAlert;
use App\Establishment\Helper\GeoHelper;
use App\Establishment\Helper\OpeningHoursHelper;
use App\Establishment\Model\SlotMatch;
use App\Repository\Establishment\EstablishmentSlotRepository;
use DateTimeImmutable;

class AlertMatchService
{
    public function __construct(
        private EstablishmentSlotRepository $slots,
        private SlotSearchService $search,
    ) {
    }

    public function distance(UserAlert $alert, EstablishmentSlot $slot): ?float
    {
        $establishment = $slot->getEstablishment();

        return GeoHelper::between($alert->getLatitude(), $alert->getLongitude(), $establishment?->getLatitude(), $establishment?->getLongitude());
    }

    public function matches(UserAlert $alert, EstablishmentSlot $slot): bool
    {
        $establishment = $slot->getEstablishment();

        if (!$alert->isEnabled() || $establishment === null || $slot->getStartsAt() === null) {
            return false;
        }

        if ($alert->getCity() !== null && $establishment->getCity() !== $alert->getCity()) {
            return false;
        }

        if (!$alert->getCategories()->isEmpty() && !$alert->getCategories()->contains($establishment->getCategory())) {
            return false;
        }

        if ($alert->getMaxPrice() !== null && $slot->getPrice() > $alert->getMaxPrice()) {
            return false;
        }

        $time = $slot->getStartsAt()->format('H:i');

        if ($alert->getStartTime() !== null && $time < $alert->getStartTime()->format('H:i')) {
            return false;
        }

        if ($alert->getEndTime() !== null && $time > $alert->getEndTime()->format('H:i')) {
            return false;
        }

        $distance = $this->distance($alert, $slot);

        return $distance === null || $distance <= $alert->getRadiusKm();
    }

    public function matchingAlerts(User $user, EstablishmentSlot $slot): array
    {
        return array_values(array_filter($user->getAlerts()->toArray(), fn (UserAlert $alert): bool => $this->matches($alert, $slot)));
    }

    public function picksFor(User $user, ?DateTimeImmutable $now = null, int $limit = 3): array
    {
        $now ??= new DateTimeImmutable();
        $picks = [];

        foreach ($user->getAlerts() as $alert) {
            if (!$alert instanceof UserAlert || !$alert->isEnabled()) {
                continue;
            }

            $city = $alert->getCity() ?? $this->search->resolveCity(null);

            if ($city === null) {
                continue;
            }

            foreach ($this->slots->search($city, $alert->getCategories()->toArray(), $now, null, $now, 100) as $slot) {
                if (!isset($picks[$slot->getId()]) && $this->matches($alert, $slot)) {
                    $picks[$slot->getId()] = new SlotMatch($slot, $this->distance($alert, $slot));
                }
            }
        }

        usort($picks, static fn (SlotMatch $a, SlotMatch $b): int => $a->getSlot()->getStartsAt() <=> $b->getSlot()->getStartsAt());

        return array_slice($picks, 0, $limit);
    }

    public function describe(UserAlert $alert): string
    {
        $parts = [];
        $categories = array_map(static fn (Category $category): string => (string) $category->getName(), $alert->getCategories()->toArray());

        $parts[] = $categories !== [] ? implode(', ', $categories) : 'Toutes catégories';
        $parts[] = sprintf('à moins de %d km', $alert->getRadiusKm());

        if ($alert->getStartTime() !== null && $alert->getEndTime() !== null) {
            $parts[] = sprintf('entre %s et %s', OpeningHoursHelper::time($alert->getStartTime()->format('H:i')), OpeningHoursHelper::time($alert->getEndTime()->format('H:i')));
        }

        return implode(' · ', $parts);
    }
}