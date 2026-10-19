<?php

namespace App\Catalog\Provider;

use App\Entity\Category;
use App\Entity\City;
use App\Entity\Company\CompanyEstablishment;
use App\Establishment\Dto\SlotSearchInput;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Presenter\SlotPresenter;
use App\Establishment\Service\SlotSearchService;
use App\Repository\Company\CompanyEstablishmentRepository;
use DateTimeImmutable;

class CityPageProvider
{
    public const SLOTS = 12;

    public const SHOPS = 24;

    private const SCAN = 100;

    public function __construct(
        private SlotSearchService $search,
        private CompanyEstablishmentRepository $establishments,
        private SlotPresenter $slotPresenter,
    ) {
    }

    public function build(City $city, ?Category $category, ?string $district, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();
        $matches = $this->search->search($this->searchInput($city, $category), $now)->getMatches();
        $establishments = $this->establishments->findForAdmin(
            $category,
            $city,
            [EstablishmentStatus::ONLINE],
            null,
            self::SHOPS,
        );
        $districts = $this->districts($establishments);
        $district = $this->selectedDistrict($district, $districts);
        $filtered = $this->inDistrict($matches, $district);
        $free = $this->freeSlots($matches);

        return [
            'count' => count($filtered),
            'slots' => $this->slotPresenter->cards(array_slice($filtered, 0, self::SLOTS), $now),
            'district' => $district,
            'districts' => $districts,
            'shops' => $this->shops($establishments, $free),
        ];
    }

    private function searchInput(City $city, ?Category $category): SlotSearchInput
    {
        $input = new SlotSearchInput();
        $input->where = $city->getSlug();
        $input->what = $category?->getSlug();
        $input->limit = self::SCAN;

        return $input;
    }

    private function selectedDistrict(?string $district, array $districts): ?string
    {
        if ($district === null || !in_array($district, $districts, true)) {
            return null;
        }

        return $district;
    }

    private function inDistrict(array $matches, ?string $district): array
    {
        if ($district === null) {
            return $matches;
        }

        $filtered = [];

        foreach ($matches as $match) {
            if ($match->getSlot()->getEstablishment()?->getLocality() === $district) {
                $filtered[] = $match;
            }
        }

        return $filtered;
    }

    private function freeSlots(array $matches): array
    {
        $free = [];

        foreach ($matches as $match) {
            $id = $match->getSlot()->getEstablishment()?->getId();
            $free[$id] = ($free[$id] ?? 0) + 1;
        }

        return $free;
    }

    private function shops(array $establishments, array $free): array
    {
        $shops = [];

        foreach ($establishments as $establishment) {
            $shops[] = $this->shop($establishment, $free[$establishment->getId()] ?? 0);
        }

        return $shops;
    }

    private function shop(CompanyEstablishment $establishment, int $free): array
    {
        return [
            'name' => $establishment->getName(),
            'locality' => $establishment->getLocality(),
            'category' => $establishment->getCategory()?->getName(),
            'city' => $establishment->getCity()?->getSlug(),
            'slug' => $establishment->getSlug(),
            'free' => $free,
        ];
    }

    private function districts(array $establishments): array
    {
        $districts = [];

        foreach ($establishments as $establishment) {
            $locality = $establishment->getLocality();

            if ($locality !== null && $locality !== '') {
                $districts[$locality] = $locality;
            }
        }

        natcasesort($districts);

        return array_values($districts);
    }
}