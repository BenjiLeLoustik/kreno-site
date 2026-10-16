<?php

namespace App\Establishment\Service;

use App\Entity\City;
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

        $latitude = $input->latitude ?? ($city->getLatitude() !== null ? (float) $city->getLatitude() : null);
        $longitude = $input->longitude ?? ($city->getLongitude() !== null ? (float) $city->getLongitude() : null);
        [$from, $to] = TimeWindowHelper::range($input->when, $now);
        $matches = [];

        foreach ($this->slots->search($city, $categories, $from, $to, $now) as $slot) {
            $establishment = $slot->getEstablishment();
            $distance = $latitude !== null && $longitude !== null && $establishment?->getLatitude() !== null && $establishment->getLongitude() !== null
                ? GeoHelper::distanceKm($latitude, $longitude, (float) $establishment->getLatitude(), (float) $establishment->getLongitude())
                : null;

            if ($input->distance !== null && $distance !== null && $distance > $input->distance) {
                continue;
            }

            $matches[] = new SlotMatch($slot, $distance);
        }

        usort($matches, match ($input->sort) {
            'nearest' => static fn (SlotMatch $a, SlotMatch $b): int => [$a->getDistance() ?? PHP_FLOAT_MAX, $a->getSlot()->getStartsAt()] <=> [$b->getDistance() ?? PHP_FLOAT_MAX, $b->getSlot()->getStartsAt()],
            'discount' => static fn (SlotMatch $a, SlotMatch $b): int => [$b->getSlot()->getDiscount(), $a->getSlot()->getStartsAt()] <=> [$a->getSlot()->getDiscount(), $b->getSlot()->getStartsAt()],
            default => static fn (SlotMatch $a, SlotMatch $b): int => $a->getSlot()->getStartsAt() <=> $b->getSlot()->getStartsAt(),
        });

        return new SlotSearchResult($city, $categories, array_slice($matches, 0, $input->limit), $latitude, $longitude);
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
}