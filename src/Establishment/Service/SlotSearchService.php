<?php

namespace App\Establishment\Service;

use App\Entity\City;
use App\Entity\Establishment\EstablishmentSlot;
use App\Establishment\Dto\SlotSearchInput;
use App\Establishment\Helper\GeoHelper;
use App\Establishment\Helper\TimeWindowHelper;
use App\Establishment\Model\SlotMatch;
use App\Establishment\Model\SlotSearchResult;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;
use App\Repository\Establishment\EstablishmentSlotRepository;
use DateTimeImmutable;

class SlotSearchService
{
    public function __construct(
        private EstablishmentSlotRepository $slots,
        private CityRepository $cities,
        private CategoryRepository $categories,
    ) {
    }

    public function search(SlotSearchInput $input, ?DateTimeImmutable $now = null): SlotSearchResult
    {
        $now ??= new DateTimeImmutable();
        $city = $this->resolveCity($input->where);
        $categories = $this->resolveCategories(array_filter([...$input->categories, $input->what]));

        if ($city === null) {
            return new SlotSearchResult(null, $categories, []);
        }

        $latitude = $input->latitude ?? $this->coordinate($city->getLatitude());
        $longitude = $input->longitude ?? $this->coordinate($city->getLongitude());
        [$from, $to] = TimeWindowHelper::range($input->when, $now);
        $matches = [];

        foreach ($this->slots->search($city, $categories, $from, $to, $now) as $slot) {
            $distance = $this->distance($slot, $latitude, $longitude);

            if ($input->distance !== null && $distance !== null && $distance > $input->distance) {
                continue;
            }

            $matches[] = new SlotMatch($slot, $distance);
        }

        usort($matches, $this->comparator($input->sort));

        return new SlotSearchResult(
            $city,
            $categories,
            array_slice($matches, 0, $input->limit),
            $latitude,
            $longitude
        );
    }

    public function resolveCity(?string $slug): ?City
    {
        if ($slug !== null && $slug !== '') {
            $city = $this->cities->findOneOpenBySlug($slug);

            if ($city !== null) {
                return $city;
            }
        }

        return $this->cities->findOpen()[0] ?? null;
    }

    private function resolveCategories(array $slugs): array
    {
        $categories = [];

        foreach (array_unique($slugs) as $slug) {
            $category = $this->categories->findOneVisibleBySlug((string) $slug);

            if ($category !== null) {
                $categories[$category->getId()] = $category;
            }
        }

        return array_values($categories);
    }

    private function coordinate(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        return (float) $value;
    }

    private function distance(EstablishmentSlot $slot, ?float $latitude, ?float $longitude): ?float
    {
        $establishment = $slot->getEstablishment();

        if ($latitude === null || $longitude === null || $establishment === null) {
            return null;
        }

        if ($establishment->getLatitude() === null || $establishment->getLongitude() === null) {
            return null;
        }

        return GeoHelper::distanceKm(
            $latitude,
            $longitude,
            (float) $establishment->getLatitude(),
            (float) $establishment->getLongitude(),
        );
    }

    private function comparator(string $sort): callable
    {
        return match ($sort) {
            'nearest' => self::byNearest(...),
            'discount' => self::byDiscount(...),
            default => self::bySoonest(...),
        };
    }

    private static function byNearest(SlotMatch $a, SlotMatch $b): int
    {
        $left = [$a->getDistance() ?? PHP_FLOAT_MAX, $a->getSlot()->getStartsAt()];
        $right = [$b->getDistance() ?? PHP_FLOAT_MAX, $b->getSlot()->getStartsAt()];

        return $left <=> $right;
    }

    private static function byDiscount(SlotMatch $a, SlotMatch $b): int
    {
        $left = [$b->getSlot()->getDiscount(), $a->getSlot()->getStartsAt()];
        $right = [$a->getSlot()->getDiscount(), $b->getSlot()->getStartsAt()];

        return $left <=> $right;
    }

    private static function bySoonest(SlotMatch $a, SlotMatch $b): int
    {
        return $a->getSlot()->getStartsAt() <=> $b->getSlot()->getStartsAt();
    }
}