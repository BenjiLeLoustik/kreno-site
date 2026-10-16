<?php

namespace App\Establishment\Service;

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
    public const SCAN = 100;

    public const PICKS = 3;

    public function __construct(
        private EstablishmentSlotRepository $slots,
        private SlotSearchService $search,
    ) {
    }

    public function distance(UserAlert $alert, EstablishmentSlot $slot): ?float
    {
        $establishment = $slot->getEstablishment();

        return GeoHelper::between(
            $alert->getLatitude(),
            $alert->getLongitude(),
            $establishment?->getLatitude(),
            $establishment?->getLongitude(),
        );
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

        $categories = $alert->getCategories();

        if (!$categories->isEmpty() && !$categories->contains($establishment->getCategory())) {
            return false;
        }

        if ($alert->getMaxPrice() !== null && $slot->getPrice() > $alert->getMaxPrice()) {
            return false;
        }

        if (!$this->isInTimeRange($alert, $slot->getStartsAt()->format('H:i'))) {
            return false;
        }

        $distance = $this->distance($alert, $slot);

        return $distance === null || $distance <= $alert->getRadiusKm();
    }

    public function matchingAlerts(User $user, EstablishmentSlot $slot): array
    {
        $alerts = [];

        foreach ($user->getAlerts() as $alert) {
            if ($this->matches($alert, $slot)) {
                $alerts[] = $alert;
            }
        }

        return $alerts;
    }

    public function picksFor(User $user, ?DateTimeImmutable $now = null, int $limit = self::PICKS): array
    {
        $now ??= new DateTimeImmutable();
        $picks = [];

        foreach ($user->getAlerts() as $alert) {
            if ($alert instanceof UserAlert && $alert->isEnabled()) {
                $picks = $this->addPicks($picks, $alert, $now);
            }
        }

        return array_slice($this->sortByStart($picks), 0, $limit);
    }

    public function describe(UserAlert $alert): string
    {
        $parts = [
            $this->categoriesLabel($alert),
            sprintf('à moins de %d km', $alert->getRadiusKm()),
        ];

        $startTime = $alert->getStartTime();
        $endTime = $alert->getEndTime();

        if ($startTime !== null && $endTime !== null) {
            $parts[] = sprintf(
                'entre %s et %s',
                OpeningHoursHelper::time($startTime->format('H:i')),
                OpeningHoursHelper::time($endTime->format('H:i')),
            );
        }

        return implode(' · ', $parts);
    }

    private function isInTimeRange(UserAlert $alert, string $time): bool
    {
        if ($alert->getStartTime() !== null && $time < $alert->getStartTime()->format('H:i')) {
            return false;
        }

        if ($alert->getEndTime() !== null && $time > $alert->getEndTime()->format('H:i')) {
            return false;
        }

        return true;
    }

    private function addPicks(array $picks, UserAlert $alert, DateTimeImmutable $now): array
    {
        $city = $alert->getCity() ?? $this->search->resolveCity(null);

        if ($city === null) {
            return $picks;
        }

        $categories = $alert->getCategories()->toArray();

        foreach ($this->slots->search($city, $categories, $now, null, $now, self::SCAN) as $slot) {
            if (!isset($picks[$slot->getId()]) && $this->matches($alert, $slot)) {
                $picks[$slot->getId()] = new SlotMatch($slot, $this->distance($alert, $slot));
            }
        }

        return $picks;
    }

    private function categoriesLabel(UserAlert $alert): string
    {
        $names = [];

        foreach ($alert->getCategories() as $category) {
            $names[] = (string) $category->getName();
        }

        if ($names === []) {
            return 'Toutes catégories';
        }

        return implode(', ', $names);
    }

    private function sortByStart(array $picks): array
    {
        usort($picks, static function (SlotMatch $a, SlotMatch $b): int {
            return $a->getSlot()->getStartsAt() <=> $b->getSlot()->getStartsAt();
        });

        return $picks;
    }
}